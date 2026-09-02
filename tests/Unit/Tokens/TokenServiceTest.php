<?php

declare(strict_types=1);

use App\Enums\TokenIssuedReason;
use App\Enums\TokenType;
use App\Exceptions\Domain\TokenExpired;
use App\Exceptions\Domain\TokenNotFound;
use App\Exceptions\Domain\TokenRevoked;
use App\Exceptions\Domain\TokenTypeMismatch;
use App\Exceptions\Domain\TokenUsed;
use App\Models\AccessToken;
use App\Models\FamilyMember;
use App\Models\Story;
use App\Models\User;
use App\Services\Tokens\TokenService;
use App\States\Story\Validated;
use Symfony\Component\Finder\Finder;

/**
 * « Quiconque détient le lien peut agir à la place du narrateur. » Ces tests
 * sont donc la première ligne de défense du produit : ils portent sur
 * l'entropie, le stockage haché, et chaque motif de refus.
 */
function tokens(): TokenService
{
    return app(TokenService::class);
}

it('issues a 43 char base64url token backed by 32 random bytes', function (): void {
    $issued = tokens()->issue(TokenType::Record, Story::factory()->recorded()->create());

    expect($issued->plain)->toHaveLength(43)
        ->and($issued->plain)->toMatch('/^[A-Za-z0-9_-]{43}$/')
        ->and(strlen(base64_decode(strtr($issued->plain, '-_', '+/').'=', true)))->toBe(32);
});

it('n’émet jamais deux fois le même jeton', function (): void {
    $story = Story::factory()->recorded()->create();

    $plains = collect(range(1, 20))
        ->map(fn (): string => tokens()->issue(TokenType::Record, $story)->plain)
        ->unique();

    expect($plains)->toHaveCount(20);
});

it('stores only the sha256 hash', function (): void {
    $issued = tokens()->issue(TokenType::Record, Story::factory()->recorded()->create());

    expect($issued->token->token_hash)->toHaveLength(64)
        ->and($issued->token->token_hash)->toMatch('/^[0-9a-f]{64}$/')
        ->and($issued->token->token_hash)->not->toBe($issued->plain)
        ->and($issued->token->token_hash)->toBe(hash('sha256', $issued->plain));

    // Le jeton en clair n'existe nulle part en base, sous aucune colonne.
    $row = DB::table('access_tokens')->where('id', $issued->token->id)->first();

    expect(json_encode($row))->not->toContain($issued->plain);
});

it('resolves a valid token of the expected type', function (): void {
    $story = Story::factory()->recorded()->create();
    $issued = tokens()->issue(TokenType::Record, $story, ['record', 'decide_share']);

    $resolved = tokens()->resolve($issued->plain, TokenType::Record);

    expect($resolved->id)->toBe($issued->token->id)
        ->and($resolved->subject_id)->toBe($story->id)
        ->and($resolved->scope)->toBe(['record', 'decide_share'])
        ->and($resolved->subject)->toBeInstanceOf(Story::class);
});

it('refuses a token of another type', function (): void {
    $issued = tokens()->issue(TokenType::Record, Story::factory()->recorded()->create());

    expect(fn () => tokens()->resolve($issued->plain, TokenType::ListenStory))
        ->toThrow(TokenTypeMismatch::class);
});

it('refuses an unknown token', function (): void {
    expect(fn () => tokens()->resolve(str_repeat('a', 43), TokenType::Record))
        ->toThrow(TokenNotFound::class);
});

it('refuses an expired token', function (): void {
    $issued = tokens()->issue(TokenType::Record, Story::factory()->recorded()->create());

    $this->travel(31)->days();

    expect(fn () => tokens()->resolve($issued->plain, TokenType::Record))->toThrow(TokenExpired::class);
});

it('refuses a revoked token', function (): void {
    $issued = tokens()->issue(TokenType::Record, Story::factory()->recorded()->create());

    tokens()->revoke($issued->token, 'test');

    expect(fn () => tokens()->resolve($issued->plain, TokenType::Record))->toThrow(TokenRevoked::class);
});

it('refuses a used single use token', function (): void {
    $issued = tokens()->issue(TokenType::Action, Story::factory()->recorded()->create()->project);

    expect($issued->token->single_use)->toBeTrue();

    tokens()->resolve($issued->plain, TokenType::Action);

    expect(fn () => tokens()->resolve($issued->plain, TokenType::Action))->toThrow(TokenUsed::class);
});

it('laisse un jeton réutilisable servir plusieurs fois', function (): void {
    $issued = tokens()->issue(TokenType::Record, Story::factory()->recorded()->create());

    tokens()->resolve($issued->plain, TokenType::Record);
    tokens()->resolve($issued->plain, TokenType::Record);

    expect($issued->token->refresh()->use_count)->toBe(2)
        ->and($issued->token->used_at)->toBeNull();
});

it('rotates a token: old revoked, new linked by replaced_by', function (): void {
    $story = Story::factory()->recorded()->create();
    $first = tokens()->issue(TokenType::Record, $story, ['record']);

    $second = tokens()->rotate($first->token, TokenIssuedReason::ResendOtherChannel);

    expect($first->token->refresh()->revoked_at)->not->toBeNull()
        ->and($first->token->replaced_by_token_id)->toBe($second->token->id)
        ->and($second->plain)->not->toBe($first->plain)
        ->and($second->token->type)->toBe(TokenType::Record)
        ->and($second->token->subject_id)->toBe($story->id)
        ->and($second->token->scope)->toBe(['record'])
        ->and($second->token->issued_reason)->toBe(TokenIssuedReason::ResendOtherChannel);

    expect(fn () => tokens()->resolve($first->plain, TokenType::Record))->toThrow(TokenRevoked::class);
    expect(tokens()->resolve($second->plain, TokenType::Record)->id)->toBe($second->token->id);
});

it('revokes all record tokens of a story when it is validated', function (): void {
    $story = Story::factory()->toReview()->create();
    $first = tokens()->issue(TokenType::Record, $story);
    $second = tokens()->issue(TokenType::Record, $story);
    $listen = tokens()->issue(TokenType::ListenStory, $story);

    $story->state->transitionTo(Validated::class);

    expect($first->token->refresh()->revoked_at)->not->toBeNull()
        ->and($second->token->refresh()->revoked_at)->not->toBeNull()
        ->and($listen->token->refresh()->revoked_at)->toBeNull();
});

it('applies the default ttl per type from config', function (): void {
    $this->freezeTime();

    $story = Story::factory()->recorded()->create();
    $familyMember = FamilyMember::factory()->create(['project_id' => $story->project_id]);

    $expectations = [
        [TokenType::Record, $story, now()->addDays(30)],
        [TokenType::ListenStory, $story, now()->addDays(90)],
        [TokenType::ListenProject, $familyMember, now()->addMonths(12)],
        [TokenType::Invitation, $story->narrator, now()->addDays(30)],
        [TokenType::Action, $story->project, now()->addDays(14)],
        [TokenType::NarratorSpace, $story->narrator, now()->addDays(30)],
        [TokenType::SensitiveGrant, $story->narrator, now()->addMinutes(15)],
    ];

    foreach ($expectations as [$type, $subject, $expected]) {
        expect(tokens()->issue($type, $subject)->token->expires_at?->getTimestamp())
            ->toBe($expected->getTimestamp(), "durée de vie inattendue pour {$type->value}");
    }

    // Le QR imprimé n'expire pas techniquement : l'engagement de durée est
    // publié et la révocation reste possible (D-8).
    expect(tokens()->issue(TokenType::Qr, $story)->token->expires_at)->toBeNull();
});

it('accepte une échéance explicite qui remplace la durée par défaut', function (): void {
    $this->freezeTime();

    $issued = tokens()->issue(
        TokenType::Record,
        Story::factory()->recorded()->create(),
        expiresAt: now()->addHours(2),
    );

    expect($issued->token->expires_at?->getTimestamp())->toBe(now()->addHours(2)->getTimestamp());
});

it('increments use_count and last_used_at on resolve', function (): void {
    $this->freezeTime();

    $issued = tokens()->issue(TokenType::Record, Story::factory()->recorded()->create());

    expect($issued->token->use_count)->toBe(0)
        ->and($issued->token->last_used_at)->toBeNull();

    tokens()->resolve($issued->plain, TokenType::Record);

    expect($issued->token->refresh()->use_count)->toBe(1)
        ->and($issued->token->last_used_at?->getTimestamp())->toBe(now()->getTimestamp());
});

it('garde la trace de qui a émis le jeton', function (): void {
    $support = User::factory()->support()->create();
    $story = Story::factory()->recorded()->create();

    $issued = tokens()->issue(
        TokenType::Record,
        $story,
        issuedBy: $support,
        reason: TokenIssuedReason::ReissueSupport,
    );

    expect($issued->token->issued_by_type)->toBe('user')
        ->and($issued->token->issued_by_id)->toBe((string) $support->id)
        ->and($issued->token->issued_reason)->toBe(TokenIssuedReason::ReissueSupport);
});

it('révoque tous les jetons d’un type pour un sujet', function (): void {
    $story = Story::factory()->recorded()->create();
    tokens()->issue(TokenType::Record, $story);
    tokens()->issue(TokenType::Record, $story);
    $qr = tokens()->issue(TokenType::Qr, $story);

    $count = tokens()->revokeAllFor($story, TokenType::Record, 'test');

    expect($count)->toBe(2)
        ->and(AccessToken::query()->whereNull('revoked_at')->count())->toBe(1)
        ->and($qr->token->refresh()->revoked_at)->toBeNull();
});

/**
 * Vérifications faites sur le jeton en clair, hors comparaison à `null` ou à
 * la chaîne vide — celles-là ne sont que des tests de présence.
 *
 * @return list<string>
 */
function plainTextComparisons(string $contents): array
{
    $harmless = ['null', "''", '""'];
    $operands = [];

    preg_match_all('/\$plain\s*[!=]=+\s*([^\s;)&|,]+)/', $contents, $rightHand);
    preg_match_all('/([^\s(!=]+)\s*[!=]=+\s*\$plain\b/', $contents, $leftHand);

    foreach ([...$rightHand[1], ...$leftHand[1]] as $operand) {
        if (! in_array($operand, $harmless, true)) {
            $operands[] = $operand;
        }
    }

    return $operands;
}

/**
 * Critère de sortie du bloc : aucun endroit du code ne compare un jeton en
 * clair, et l'empreinte ne se lit qu'à un seul endroit.
 */
it('ne compare jamais un jeton en clair', function (): void {
    $offenders = [];

    foreach (Finder::create()->files()->name('*.php')->in(base_path('app')) as $file) {
        $contents = $file->getContents();

        // Une requête sur une colonne `token` nue voudrait dire qu'un jeton
        // est stocké en clair quelque part. Le jeton de réinitialisation de
        // mot de passe de Fortify est un autre mécanisme, haché par le
        // framework, et ne passe pas par `access_tokens`.
        $queriesAClearColumn = preg_match('/where\(\s*[\'"]token[\'"]/', $contents) === 1;

        if (plainTextComparisons($contents) !== [] || $queriesAClearColumn) {
            $offenders[] = $file->getRelativePathname();
        }
    }

    expect($offenders)->toBe([]);
});

it('reconnaît quand même une vraie comparaison de jeton', function (): void {
    expect(plainTextComparisons('if ($plain === $expected) {}'))->toBe(['$expected'])
        ->and(plainTextComparisons('if ($token->secret === $plain) {}'))->toBe(['$token->secret'])
        ->and(plainTextComparisons("if (\$plain !== '') {}"))->toBe([])
        ->and(plainTextComparisons('if ($plain === null) {}'))->toBe([]);
});

it('ne lit l’empreinte du jeton que dans le service de jetons', function (): void {
    $allowed = ['Services/Tokens/TokenService.php', 'Models/AccessToken.php'];
    $offenders = [];

    foreach (Finder::create()->files()->name('*.php')->in(base_path('app')) as $file) {
        if (str_contains($file->getContents(), 'token_hash')
            && ! in_array($file->getRelativePathname(), $allowed, true)) {
            $offenders[] = $file->getRelativePathname();
        }
    }

    expect($offenders)->toBe([]);
});
