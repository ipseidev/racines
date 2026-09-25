<?php

declare(strict_types=1);

use App\Enums\TokenType;
use App\Models\Story;
use App\Models\User;
use App\Services\Tokens\TokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Microsoft Clarity : le site marchand, et rien d'autre.
 *
 * Une relecture de session emporte le texte de la page. La règle est donc
 * plus étroite que pour les trois autres mesures : ni page à jeton, **ni
 * espace de compte**, où s'affichent les récits d'une famille. Et comme
 * partout, la garde est au serveur — l'identifiant et les origines de la
 * politique de contenu manquent tous les deux là où Clarity n'a rien à faire.
 */
beforeEach(function (): void {
    config()->set('services.clarity.enabled', true);
    config()->set('services.clarity.project_id', 'clarite01');
});

function politique(string $url): string
{
    return (string) test()->get($url)->headers->get('Content-Security-Policy');
}

it('donne l’identifiant et ouvre ses origines sur l’accueil', function (): void {
    $this->get('/')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('clarity.projectId', 'clarite01'));

    $csp = politique('/');

    expect($csp)->toMatch('/script-src[^;]*https:\/\/www\.clarity\.ms https:\/\/scripts\.clarity\.ms/')
        ->and($csp)->toMatch('/connect-src[^;]*https:\/\/\*\.clarity\.ms/')
        ->and($csp)->toMatch('/img-src[^;]*https:\/\/c\.bing\.com/')
        ->and($csp)->not->toContain('unsafe-eval');
});

it('ne donne rien à une page à jeton', function (): void {
    $story = Story::factory()->proposed()->create();
    $issued = app(TokenService::class)->issue(TokenType::Record, $story);

    $reponse = $this->get("/r/{$issued->plain}");

    $reponse->assertInertia(fn ($page) => $page->where('clarity', null));

    expect((string) $reponse->headers->get('Content-Security-Policy'))->not->toContain('clarity');
});

it('ne donne rien aux espaces d’un compte', function (string $url): void {
    $this->actingAs(User::factory()->create());

    $reponse = $this->get($url);

    expect((string) $reponse->headers->get('Content-Security-Policy'))->not->toContain('clarity')
        ->and((string) $reponse->getContent())->not->toContain('clarite01');
})->with(['/espace', '/settings/profile']);

it('ne donne rien tant que l’interrupteur est éteint', function (): void {
    // L'identifiant peut traîner dans un `.env` : il ne suffit pas (T-61).
    config()->set('services.clarity.enabled', false);

    $this->get('/')->assertInertia(fn ($page) => $page->where('clarity', null));

    expect(politique('/'))->not->toContain('clarity');
});
