<?php

declare(strict_types=1);

use App\Books\IssueQrToken;
use App\Enums\TokenType;
use App\Models\AccessToken;
use App\Models\Book;
use App\Models\BookChapter;
use App\Models\Story;
use App\Services\Tokens\TokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Le QR d'un chapitre : émis une fois, reproductible, révocable.
 *
 * Les trois propriétés répondent à trois situations réelles : regénérer un
 * BAT ne doit pas changer le code, réimprimer non plus, et un narrateur qui
 * retire son récit doit pouvoir éteindre le code **de tous les exemplaires
 * déjà imprimés** en un geste — ce qui suppose qu'il n'y en ait qu'un.
 */
function chapitre(): BookChapter
{
    $story = Story::factory()->validated()->create();
    $book = Book::factory()->create(['project_id' => $story->project_id]);
    $chapter = new BookChapter(['position' => 10, 'included' => true]);
    $chapter->story()->associate($story);
    $chapter->book()->associate($book);
    $chapter->save();

    return $chapter;
}

it('émet un jeton qr porté par l’histoire, sans expiration', function (): void {
    $chapter = chapitre();
    $token = app(IssueQrToken::class)->handle($chapter);

    expect($token->type)->toBe(TokenType::Qr)
        ->and($token->expires_at)->toBeNull()
        ->and($token->subject_id)->toBe($chapter->story_id)
        ->and($chapter->refresh()->qr_token_id)->toBe($token->getKey());
});

it('ne stocke que l’empreinte, jamais le code imprimé', function (): void {
    $chapter = chapitre();
    app(IssueQrToken::class)->handle($chapter);

    $plain = IssueQrToken::plainFor($chapter);
    $ligne = DB::table('access_tokens')->where('token_hash', TokenService::hash($plain))->first();

    expect($ligne)->not->toBeNull()
        // Le code imprimé ne doit apparaître dans aucune colonne : un vidage
        // de base ne doit pas rendre des liens qui fonctionnent.
        ->and(json_encode((array) $ligne))->not->toContain($plain);
});

it('rend le même code à chaque regénération', function (): void {
    $chapter = chapitre();

    $premier = app(IssueQrToken::class)->handle($chapter);
    $second = app(IssueQrToken::class)->handle($chapter->refresh());

    expect($second->getKey())->toBe($premier->getKey())
        ->and(AccessToken::query()->where('type', TokenType::Qr->value)->count())->toBe(1);
});

it('fait quarante-trois caractères, comme la route l’exige', function (): void {
    $plain = IssueQrToken::plainFor(chapitre());

    expect($plain)->toHaveLength(43)
        ->and($plain)->toMatch('/^[A-Za-z0-9_-]{43}$/');
});

it('donne des codes différents à deux chapitres', function (): void {
    expect(IssueQrToken::plainFor(chapitre()))->not->toBe(IssueQrToken::plainFor(chapitre()));
});

it('se laisse résoudre par le service de jetons', function (): void {
    $chapter = chapitre();
    app(IssueQrToken::class)->handle($chapter);

    $resolved = app(TokenService::class)->resolve(IssueQrToken::plainFor($chapter), TokenType::Qr);

    expect($resolved->subject_id)->toBe($chapter->story_id);
});
