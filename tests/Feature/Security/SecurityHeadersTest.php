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
    $response = $this->get('/');
    $html = (string) $response->getContent();
    $csp = (string) $response->headers->get('Content-Security-Policy');

    expect($html)->toContain('<script nonce="')
        ->and($html)->toContain('<style nonce="');

    preg_match("/script-src 'self' 'nonce-([^']+)'/", $csp, $matches);
    $nonce = $matches[1] ?? null;

    expect($nonce)->not->toBeNull()
        ->and($html)->toContain('<script nonce="'.$nonce.'"');
});

it('donne son nonce au front, qui crée des styles après le rendu', function (): void {
    $response = $this->get('/');
    $html = (string) $response->getContent();
    $csp = (string) $response->headers->get('Content-Security-Policy');

    preg_match("/style-src 'self' 'nonce-([^']+)'/", $csp, $matches);
    $nonce = $matches[1] ?? null;

    // Inertia injecte la feuille de styles de sa barre de progression à
    // l'exécution. Sans ce nonce, elle est refusée sur `style-src-elem` et
    // l'indicateur de chargement ne s'affiche pas (T-75). La balise et
    // l'en-tête doivent porter la **même** valeur, sinon le nonce ne sert
    // qu'à donner l'illusion d'une protection.
    expect($nonce)->not->toBeNull()
        ->and($html)->toContain('<meta name="csp-nonce" content="'.$nonce.'">');
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
    $story = Story::factory()->proposed()->create();
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
    $story = Story::factory()->proposed()->create();
    $issued = app(TokenService::class)->issue(TokenType::Record, $story);

    // `no-store` passe après : c'est lui qui décide, et il est plus strict.
    expect($this->get("/r/{$issued->plain}")->headers->get('Referrer-Policy'))
        ->toBe('no-referrer');

    expect($this->get('/')->headers->get('Referrer-Policy'))
        ->toBe('strict-origin-when-cross-origin');
});

/**
 * Le serveur de développement de Vite, et lui seul, et jamais en production.
 *
 * Défaut trouvé en test humain : la politique refusait
 * `ws://localhost:5176`, donc le websocket de rechargement à chaud ne se
 * connectait pas. Vite retombait alors sur une invalidation de module, qui
 * réimportait `app.tsx` avec un `?t=` — **deux instances du module, deux
 * appels à `createRoot` sur le même conteneur**. Une racine React mettait
 * l'URL à jour, l'autre gardait l'écran : plus aucune navigation côté client
 * ne fonctionnait en local. Invisible en intégration continue, qui construit
 * les assets et ne lance donc jamais le serveur de développement.
 */
it('autorise le serveur de développement de Vite quand il tourne, et lui seul', function (): void {
    $hot = public_path('hot');
    $existing = is_file($hot) ? (string) file_get_contents($hot) : null;
    file_put_contents($hot, 'http://localhost:5176');

    try {
        $csp = (string) $this->get('/')->headers->get('Content-Security-Policy');

        expect($csp)->toContain('http://localhost:5176')
            ->and($csp)->toContain('ws://localhost:5176')
            // L'assouplissement ne touche que `connect-src` : les scripts
            // restent tenus par leur nonce.
            ->and($csp)->toMatch("/connect-src[^;]*ws:\/\/localhost:5176/")
            ->and($csp)->not->toMatch('/script-src[^;]*localhost:5176/')
            // Le websocket n'apparaît que là où il sert.
            ->and($csp)->not->toMatch('/img-src[^;]*ws:/')
            ->and($csp)->not->toMatch('/media-src[^;]*ws:/');
    } finally {
        $existing === null ? @unlink($hot) : file_put_contents($hot, $existing);
    }
});

it('n’autorise aucune origine de développement quand Vite ne tourne pas', function (): void {
    $hot = public_path('hot');
    $existing = is_file($hot) ? (string) file_get_contents($hot) : null;
    @unlink($hot);

    try {
        $csp = (string) $this->get('/')->headers->get('Content-Security-Policy');

        expect($csp)->not->toContain('ws://')
            ->and($csp)->not->toContain(':5176');
    } finally {
        if ($existing !== null) {
            file_put_contents($hot, $existing);
        }
    }
});

it('refuse d’assouplir la politique en production, même avec un fichier hot', function (): void {
    // Un `hot` oublié dans une image de production ne doit pas ouvrir une
    // origine de développement dans la politique de sécurité.
    $hot = public_path('hot');
    $existing = is_file($hot) ? (string) file_get_contents($hot) : null;
    file_put_contents($hot, 'http://localhost:5176');
    app()->detectEnvironment(fn (): string => 'production');

    try {
        $csp = (string) $this->get('/')->headers->get('Content-Security-Policy');

        expect($csp)->not->toContain(':5176');
    } finally {
        $existing === null ? @unlink($hot) : file_put_contents($hot, $existing);
    }
});
