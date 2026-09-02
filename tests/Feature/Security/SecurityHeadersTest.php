<?php

declare(strict_types=1);

use App\Enums\TokenType;
use App\Models\Story;
use App\Models\User;
use App\Services\Tokens\TokenService;

it('sert une politique de contenu stricte, par nonce, sur les pages du produit', function (): void {
    $response = $this->get('/');
    $csp = (string) $response->headers->get('Content-Security-Policy');

    expect($csp)->toContain("default-src 'self'")
        ->and($csp)->toMatch("/script-src 'self' 'nonce-[A-Za-z0-9+\\/=]+'/")
        ->and($csp)->toMatch("/style-src 'self' 'nonce-[A-Za-z0-9+\\/=]+'/")
        ->and($csp)->toContain("object-src 'none'")
        ->and($csp)->toContain("base-uri 'self'")
        ->and($csp)->toContain("form-action 'self'")
        ->and($csp)->toContain("frame-ancestors 'none'")
        // Ni `unsafe-eval` ni `unsafe-inline` pour les scripts.
        ->and($csp)->not->toContain('unsafe-eval')
        ->and($csp)->not->toMatch('/script-src[^;]*unsafe-inline/');
});

it('n’autorise aucune origine de police tierce : elles sont auto-hébergées', function (): void {
    $csp = (string) $this->get('/')->headers->get('Content-Security-Policy');

    expect($csp)->toContain("font-src 'self' data:")
        ->and($csp)->not->toContain('fonts.googleapis.com')
        ->and($csp)->not->toContain('fonts.gstatic.com');
});

it('pose le nonce sur le script et le style en ligne de la vue racine', function (): void {
    $html = $this->get('/')->getContent();
    $csp = (string) $this->get('/')->headers->get('Content-Security-Policy');

    expect($html)->toContain('<script nonce="')
        ->and($html)->toContain('<style nonce="');

    preg_match("/script-src 'self' 'nonce-([^']+)'/", $csp, $matches);

    expect($matches[1] ?? null)->not->toBeNull();
});

it('autorise les origines de médias déclarées dans la configuration', function (): void {
    config()->set('product.security.media_hosts', ['https://compte.eu.r2.cloudflarestorage.com']);

    $csp = (string) $this->get('/')->headers->get('Content-Security-Policy');

    expect($csp)->toContain('media-src \'self\' blob: https://compte.eu.r2.cloudflarestorage.com')
        ->and($csp)->toContain('img-src \'self\' data: blob: https://compte.eu.r2.cloudflarestorage.com');
});

it('assouplit la politique pour le back-office, qu’Alpine exige', function (): void {
    $csp = (string) $this->actingAs(User::factory()->admin()->create())
        ->get('/admin')
        ->headers->get('Content-Security-Policy');

    expect($csp)->toContain("script-src 'self' 'unsafe-eval' 'unsafe-inline'")
        ->and($csp)->toContain("frame-ancestors 'none'");
});

it('n’autorise le micro que sur les pages d’enregistrement', function (): void {
    $story = Story::factory()->recorded()->create();
    $issued = app(TokenService::class)->issue(TokenType::Record, $story);

    expect($this->get("/r/{$issued->plain}")->headers->get('Permissions-Policy'))
        ->toContain('microphone=(self)');

    expect($this->get('/')->headers->get('Permissions-Policy'))
        ->toContain('microphone=()');
});

it('interdit partout la caméra et la géolocalisation', function (): void {
    $policy = (string) $this->get('/')->headers->get('Permissions-Policy');

    expect($policy)->toContain('camera=()')
        ->and($policy)->toContain('geolocation=()');
});

it('pose nosniff et refuse l’encadrement', function (): void {
    $response = $this->get('/');

    expect($response->headers->get('X-Content-Type-Options'))->toBe('nosniff')
        ->and($response->headers->get('X-Frame-Options'))->toBe('DENY');
});

it('n’annonce HSTS que sur une connexion chiffrée', function (): void {
    expect($this->get('http://localhost/')->headers->get('Strict-Transport-Security'))->toBeNull();

    expect($this->get('https://localhost/')->headers->get('Strict-Transport-Security'))
        ->toBe('max-age=31536000; includeSubDomains');
});

it('laisse la page à jeton imposer son propre référent', function (): void {
    $story = Story::factory()->recorded()->create();
    $issued = app(TokenService::class)->issue(TokenType::Record, $story);

    // `no-store` passe après : c'est lui qui décide, et il est plus strict.
    expect($this->get("/r/{$issued->plain}")->headers->get('Referrer-Policy'))
        ->toBe('no-referrer');

    expect($this->get('/')->headers->get('Referrer-Policy'))
        ->toBe('strict-origin-when-cross-origin');
});
