<?php

declare(strict_types=1);

use App\Enums\TokenType;
use App\Models\OtpChallenge;
use App\Models\Story;
use App\Services\Tokens\TokenService;
use App\Support\SensitiveGrant;
use Database\Factories\OtpChallengeFactory;
use Illuminate\Support\Facades\Route;

/**
 * La route protégée est déclarée ici plutôt que dans `routes/narrator.php` :
 * aucune action sensible du produit n'existe encore, et le bloc 03 n'a pas à
 * en inventer une. Le bloc 07 branchera les vrais retraits sur ce middleware.
 */
beforeEach(function (): void {
    Route::middleware(['web', 'throttle:tokens', 'no-store', 'resolve.token:record', 'sensitive'])
        ->get('/r/{token}/acte-sensible', fn (): string => 'autorisé')
        ->name('narrator.test.sensitive');
});

function sensitiveLink(): array
{
    $story = Story::factory()->proposed()->create();
    $issued = app(TokenService::class)->issue(TokenType::Record, $story);

    return [$issued, $story];
}

it('envoie au défi par code quand aucune autorisation n’est présentée', function (): void {
    [$issued] = sensitiveLink();

    $this->get("/r/{$issued->plain}/acte-sensible")
        ->assertRedirect("/r/{$issued->plain}/code");
});

it('laisse passer avec une autorisation fraîche', function (): void {
    [$issued, $story] = sensitiveLink();

    $grant = app(TokenService::class)->issue(TokenType::SensitiveGrant, $story->narrator);

    $this->withCookie(SensitiveGrant::COOKIE, $grant->plain)
        ->get("/r/{$issued->plain}/acte-sensible")
        ->assertOk()
        ->assertSee('autorisé');
});

it('redemande un code quand l’autorisation a expiré', function (): void {
    [$issued, $story] = sensitiveLink();

    $grant = app(TokenService::class)->issue(TokenType::SensitiveGrant, $story->narrator);

    $this->travel(16)->minutes();

    $this->withCookie(SensitiveGrant::COOKIE, $grant->plain)
        ->get("/r/{$issued->plain}/acte-sensible")
        ->assertRedirect("/r/{$issued->plain}/code");
});

it('ne laisse pas rejouer une autorisation déjà consommée', function (): void {
    [$issued, $story] = sensitiveLink();

    $grant = app(TokenService::class)->issue(TokenType::SensitiveGrant, $story->narrator);

    $this->withCookie(SensitiveGrant::COOKIE, $grant->plain)
        ->get("/r/{$issued->plain}/acte-sensible")
        ->assertOk();

    $this->withCookie(SensitiveGrant::COOKIE, $grant->plain)
        ->get("/r/{$issued->plain}/acte-sensible")
        ->assertRedirect("/r/{$issued->plain}/code");
});

it('refuse une autorisation qui n’est pas du bon type', function (): void {
    [$issued, $story] = sensitiveLink();

    $wrongType = app(TokenService::class)->issue(TokenType::NarratorSpace, $story->narrator);

    $this->withCookie(SensitiveGrant::COOKIE, $wrongType->plain)
        ->get("/r/{$issued->plain}/acte-sensible")
        ->assertRedirect("/r/{$issued->plain}/code");
});

it('pose l’autorisation dans un cookie httpOnly et strict, jamais dans l’URL', function (): void {
    [$issued, $story] = sensitiveLink();
    $challenge = OtpChallenge::factory()->create(['narrator_id' => $story->narrator_id]);

    $response = $this->post("/r/{$issued->plain}/code/verify", ['code' => OtpChallengeFactory::CODE]);

    $cookie = collect($response->headers->getCookies())
        ->firstWhere(fn ($cookie): bool => $cookie->getName() === SensitiveGrant::COOKIE);

    expect($cookie)->not->toBeNull()
        ->and($cookie->isHttpOnly())->toBeTrue()
        ->and($cookie->getSameSite())->toBe('strict')
        ->and($response->headers->get('Location'))->not->toContain((string) $cookie->getValue());

    expect($challenge->refresh()->verified_at)->not->toBeNull();
});

it('affiche le défi et permet de demander un code', function (): void {
    [$issued] = sensitiveLink();

    $this->get("/r/{$issued->plain}/code")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('narrator/OtpChallenge')
            ->where('sentToMasked', null));

    $this->post("/r/{$issued->plain}/code")->assertRedirect();

    $this->get("/r/{$issued->plain}/code")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('sentToMasked', fn (?string $masked): bool => $masked !== null
            && ! str_contains((string) $masked, '00000')));
});

it('limite les demandes de code à trois par heure', function (): void {
    [$issued] = sensitiveLink();

    for ($i = 0; $i < 3; $i++) {
        $this->post("/r/{$issued->plain}/code")->assertRedirect();
    }

    $this->post("/r/{$issued->plain}/code")->assertStatus(429);
});

it('refuse un code invalide avec un message en langage simple', function (): void {
    [$issued, $story] = sensitiveLink();
    OtpChallenge::factory()->create(['narrator_id' => $story->narrator_id]);

    $this->post("/r/{$issued->plain}/code/verify", ['code' => '000000'])
        ->assertSessionHasErrors(['code' => __('narrator.otp.invalid')]);
});
