<?php

declare(strict_types=1);

use App\Enums\OtpPurpose;
use App\Enums\TokenType;
use App\Models\AccessToken;
use App\Models\Narrator;
use App\Models\OtpChallenge;
use App\Models\Project;
use App\Models\Story;
use App\Services\Tokens\TokenService;
use App\Support\SensitiveGrant;
use Database\Factories\OtpChallengeFactory;

/**
 * Un jeton d'espace narrateur, comme celui posé après vérification du code.
 *
 * @return array{string, Narrator}
 */
function spaceLink(?Narrator $narrator = null): array
{
    $narrator ??= Narrator::factory()->primary()->create();
    $issued = app(TokenService::class)->issue(TokenType::NarratorSpace, $narrator, ['read', 'withdraw']);

    return [$issued->plain, $narrator];
}

it('demande un code avant d’ouvrir l’espace', function (): void {
    fakeSms();
    $narrator = Narrator::factory()->primary()->create(['phone_e164' => '+33600000009']);

    $this->get('/n/request')->assertOk()
        ->assertInertia(fn ($page) => $page->component('narrator/SpaceRequest'));

    $this->post('/n/request', ['identifier' => '+33600000009'])->assertRedirect();

    // Le code part sur la coordonnée déjà connue : on ne demande jamais au
    // narrateur de saisir une adresse qu'on ne détient pas.
    expect(OtpChallenge::query()
        ->where('narrator_id', $narrator->id)
        ->where('purpose', OtpPurpose::NarratorSpace->value)
        ->count())->toBe(1);
});

it('ne dit pas si la coordonnée est connue', function (): void {
    fakeSms();

    // Une réponse différente ferait de cette page un annuaire : « ce numéro
    // est-il chez vous ? ».
    $known = $this->post('/n/request', ['identifier' => '+33600000009']);
    $unknown = $this->post('/n/request', ['identifier' => '+33699999999']);

    expect($unknown->status())->toBe($known->status())
        ->and(OtpChallenge::query()->count())->toBe(0);
});

it('pose le jeton d’espace en cookie après un code valable', function (): void {
    $narrator = Narrator::factory()->primary()->create(['phone_e164' => '+33600000009']);
    $challenge = OtpChallenge::factory()->forNarratorSpace()->create(['narrator_id' => $narrator->id]);

    $this->post('/n/verify', [
        'identifier' => '+33600000009',
        'code' => OtpChallengeFactory::CODE,
    ])->assertRedirect();

    expect($challenge->refresh()->verified_at)->not->toBeNull()
        ->and(AccessToken::query()
            ->where('type', TokenType::NarratorSpace->value)
            ->where('subject_id', $narrator->id)
            ->whereNull('revoked_at')
            ->count())->toBe(1);
});

it('refuse un code faux et n’ouvre rien', function (): void {
    $narrator = Narrator::factory()->primary()->create(['phone_e164' => '+33600000009']);
    OtpChallenge::factory()->forNarratorSpace()->create(['narrator_id' => $narrator->id]);

    $this->post('/n/verify', ['identifier' => '+33600000009', 'code' => '000000'])
        ->assertSessionHasErrors('code');

    expect(AccessToken::query()->where('type', TokenType::NarratorSpace->value)->count())->toBe(0);
});

it('liste les histoires du narrateur, avec un libellé en langage simple', function (): void {
    $project = Project::factory()->create();
    $narrator = Narrator::factory()->primary()->create(['project_id' => $project->id]);

    Story::factory()->forProject($project)->shared()->create(['narrator_id' => $narrator->id, 'title' => 'Les crêpes']);
    Story::factory()->forProject($project)->transcribed()->create(['narrator_id' => $narrator->id, 'title' => 'Le potager']);
    Story::factory()->forProject($project)->hidden()->create(['narrator_id' => $narrator->id, 'title' => 'Le déménagement']);
    // Une histoire d'un autre narrateur du même projet : hors de son espace.
    $other = Narrator::factory()->create(['project_id' => $project->id]);
    Story::factory()->forProject($project)->shared()->create([
        'narrator_id' => $other->id,
        'title' => 'Chez quelqu’un d’autre',
    ]);

    [$token] = spaceLink($narrator);

    $this->get("/n/{$token}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('narrator/Space')
            ->has('stories', 3)
            ->where('firstName', $narrator->first_name),
        );
});

it('ne montre jamais les histoires d’un autre narrateur', function (): void {
    [$token] = spaceLink();
    $other = Story::factory()->shared()->create(['title' => 'Récit d’un autre']);

    $content = (string) $this->get("/n/{$token}")->getContent();

    expect($content)->not->toContain('Récit d’un autre')
        ->and($content)->not->toContain($other->id);
});

it('refuse un jeton d’enregistrement sur l’espace', function (): void {
    $story = Story::factory()->shared()->create();
    $record = app(TokenService::class)->issue(TokenType::Record, $story, ['record']);

    // Le périmètre est strict : un lien d'enregistrement ne donne pas accès à
    // toutes les histoires de la personne.
    $this->get("/n/{$record->plain}")->assertNotFound();
});

it('envoie au code pour un acte sensible, sans autorisation fraîche', function (): void {
    [$token, $narrator] = spaceLink();
    $story = Story::factory()->shared()->create(['narrator_id' => $narrator->id, 'project_id' => $narrator->project_id]);

    $this->post("/n/{$token}/stories/{$story->id}/trash")
        ->assertRedirect(route('narrator.space.otp.show', ['token' => $token]));
});

it('laisse passer un acte sensible avec une autorisation fraîche', function (): void {
    [$token, $narrator] = spaceLink();
    $story = Story::factory()->shared()->create(['narrator_id' => $narrator->id, 'project_id' => $narrator->project_id]);
    $grant = app(TokenService::class)->issue(TokenType::SensitiveGrant, $narrator);

    $this->withCookie(SensitiveGrant::COOKIE, $grant->plain)
        ->post("/n/{$token}/stories/{$story->id}/trash")
        ->assertRedirect();

    expect($story->refresh()->trashed_at)->not->toBeNull();
});

it('refuse d’agir sur l’histoire d’un autre narrateur', function (): void {
    [$token, $narrator] = spaceLink();
    $stranger = Story::factory()->shared()->create();
    $grant = app(TokenService::class)->issue(TokenType::SensitiveGrant, $narrator);

    $this->withCookie(SensitiveGrant::COOKIE, $grant->plain)
        ->post("/n/{$token}/stories/{$stranger->id}/trash")
        ->assertNotFound();

    expect($stranger->refresh()->trashed_at)->toBeNull();
});

it('demande une pause des questions', function (): void {
    [$token, $narrator] = spaceLink();
    $grant = app(TokenService::class)->issue(TokenType::SensitiveGrant, $narrator);

    $this->withCookie(SensitiveGrant::COOKIE, $grant->plain)
        ->post("/n/{$token}/pause", ['weeks' => 4])
        ->assertRedirect();

    // Une pause n'est pas un abandon : elle a une fin, et le bloc 09 reprend
    // le fil tout seul.
    expect($narrator->project->refresh()->paused_until)->not->toBeNull();
});

it('ne met jamais l’espace en cache', function (): void {
    [$token] = spaceLink();

    $response = $this->get("/n/{$token}");

    expect($response->headers->get('cache-control'))->toContain('no-store')
        ->and($response->headers->get('x-robots-tag'))->toBe('noindex, nofollow');
});

it('borne les demandes de code sur la coordonnée, pas sur l’IP seule', function (): void {
    fakeSms();
    Narrator::factory()->primary()->create(['phone_e164' => '+33600000001']);
    Narrator::factory()->primary()->create(['phone_e164' => '+33600000002']);

    // Trois demandes pour un numéro : la quatrième est refusée.
    for ($i = 0; $i < 3; $i++) {
        $this->post('/n/request', ['identifier' => '+33600000001'])->assertRedirect();
    }

    $this->post('/n/request', ['identifier' => '+33600000001'])->assertStatus(429);

    // Mais le voisin, sur la même connexion, passe encore : une maison de
    // retraite partage une IP, et personne ne doit y rester dehors.
    $this->post('/n/request', ['identifier' => '+33600000002'])->assertRedirect();
});

it('redirige vers l’espace sur le domaine des liens, port compris', function (): void {
    $narrator = Narrator::factory()->primary()->create(['phone_e164' => '+33600000009']);
    OtpChallenge::factory()->forNarratorSpace()->create(['narrator_id' => $narrator->id]);

    $response = $this->post('/n/verify', [
        'identifier' => '+33600000009',
        'code' => OtpChallengeFactory::CODE,
    ]);

    // Le port compte : en local l'application n'écoute pas sur 80, et une URL
    // qui l'oublie envoie le narrateur nulle part au milieu de son parcours.
    $target = (string) $response->headers->get('Location');
    $issued = AccessToken::query()->where('type', TokenType::NarratorSpace->value)->sole();

    expect($target)->toStartWith(rtrim((string) config('app.url'), '/').'/n/')
        ->and($issued->subject_id)->toBe($narrator->id);
});
