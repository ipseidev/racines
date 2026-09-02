<?php

declare(strict_types=1);

use App\Enums\TokenType;
use App\Models\AccessToken;
use App\Models\FamilyMember;
use App\Models\Story;
use App\Services\Tokens\TokenService;
use App\States\Story\Validated;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

function issueRecordLink(?Story $story = null): array
{
    $story ??= Story::factory()->proposed()->create();
    $issued = app(TokenService::class)->issue(TokenType::Record, $story);

    return [$issued, $story];
}

it('binds the token and subject to the request for a valid record token', function (): void {
    [$issued, $story] = issueRecordLink();

    $this->get("/r/{$issued->plain}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('narrator/Record')
            ->where('question', $story->questionText())
            ->where('firstName', $story->narrator->first_name)
            // L'identifiant de l'histoire ne circule pas jusqu'au navigateur :
            // le brouillon local est indexé par une empreinte.
            ->where('storyRef', hash('sha256', $story->id)));
});

it('renders the friendly page with reason expired', function (): void {
    [$issued] = issueRecordLink();

    $this->travel(31)->days();

    $this->get("/r/{$issued->plain}")
        ->assertStatus(410)
        ->assertInertia(fn ($page) => $page
            ->component('narrator/LinkUnavailable')
            ->where('reason', 'expired')
            ->where('canRequestNewLink', true));
});

it('renders the friendly page with reason revoked', function (): void {
    [$issued] = issueRecordLink();

    app(TokenService::class)->revoke($issued->token, 'test');

    $this->get("/r/{$issued->plain}")
        ->assertStatus(410)
        ->assertInertia(fn ($page) => $page
            ->component('narrator/LinkUnavailable')
            ->where('reason', 'revoked')
            ->where('canRequestNewLink', true));
});

it('renders the friendly page with reason not_found', function (): void {
    $this->get('/r/'.str_repeat('z', 43))
        ->assertStatus(404)
        ->assertInertia(fn ($page) => $page
            ->component('narrator/LinkUnavailable')
            ->where('reason', 'not_found')
            ->where('canRequestNewLink', false));
});

it('refuse un jeton d’un autre périmètre sans révéler qu’il existe', function (): void {
    $story = Story::factory()->recorded()->create();
    $issued = app(TokenService::class)->issue(TokenType::ListenStory, $story);

    $this->get("/r/{$issued->plain}")
        ->assertStatus(404)
        ->assertInertia(fn ($page) => $page->component('narrator/LinkUnavailable'));
});

it('sert la page famille pour un lien d’écoute mort', function (): void {
    Route::middleware(['web', 'throttle:tokens', 'no-store', 'resolve.token:listen_project'])
        ->get('/l/{token}', fn (): string => 'ok');

    $member = FamilyMember::factory()->create();
    $issued = app(TokenService::class)->issue(TokenType::ListenProject, $member);
    app(TokenService::class)->revoke($issued->token, 'test');

    $this->get("/l/{$issued->plain}")
        ->assertStatus(410)
        ->assertInertia(fn ($page) => $page
            ->component('family/LinkUnavailable')
            ->where('reason', 'revoked')
            // Un proche ne redemande pas de lien au produit : il le redemande
            // à la personne qui l'a invité.
            ->where('canRequestNewLink', false));
});

it('returns 404 for a malformed token without hitting the database', function (): void {
    foreach (['trop-court', str_repeat('a', 44), 'avec.point'.str_repeat('a', 32)] as $malformed) {
        DB::enableQueryLog();
        DB::flushQueryLog();

        $this->get("/r/{$malformed}")->assertNotFound();

        $queries = collect(DB::getQueryLog())->pluck('query')->filter(
            fn (string $sql): bool => str_contains($sql, 'access_tokens'),
        );

        expect($queries)->toBeEmpty("le jeton mal formé [{$malformed}] a déclenché une requête");
    }
});

it('adds no-store, noindex and no-referrer headers on token pages', function (): void {
    [$issued] = issueRecordLink();

    $response = $this->get("/r/{$issued->plain}");

    expect($response->headers->get('Cache-Control'))->toContain('no-store')
        ->and($response->headers->get('X-Robots-Tag'))->toBe('noindex, nofollow')
        ->and($response->headers->get('Referrer-Policy'))->toBe('no-referrer');
});

it('rate limits token routes at 60 per minute per ip', function (): void {
    [$issued] = issueRecordLink();

    // La limite par jeton (20) mord avant celle par IP (60) : on éprouve donc
    // la plus stricte des deux, avec des jetons distincts pour l'IP.
    for ($i = 0; $i < 20; $i++) {
        $this->get("/r/{$issued->plain}")->assertOk();
    }

    $this->get("/r/{$issued->plain}")->assertStatus(429);
});

it('ne met jamais le jeton en clair dans la clé du limiteur', function (): void {
    [$issued] = issueRecordLink();

    $this->get("/r/{$issued->plain}")->assertOk();

    $keys = collect(DB::table('cache')->pluck('key'));

    expect($keys)->not->toBeEmpty()
        ->and($keys->filter(fn (string $key): bool => str_contains($key, $issued->plain)))->toBeEmpty();
})->skip(fn (): bool => config('cache.default') !== 'database', 'Le magasin de cache des tests ne persiste pas les clés.');

it('toutes les routes par jeton portent les trois protections', function (): void {
    $routes = collect(Route::getRoutes()->getRoutes())->filter(
        fn ($route): bool => preg_match('#^(r|n|l|q|i|a|x)/#', (string) $route->uri()) === 1,
    );

    expect($routes)->not->toBeEmpty();

    /*
     * Trois exceptions, et chacune est une porte d'entrée : par construction,
     * il n'y a pas encore de jeton à résoudre.
     *
     *  - la demande d'un nouveau lien agit justement parce que le lien est
     *    mort ;
     *  - les deux étapes d'accès à l'espace narrateur précèdent l'émission du
     *    jeton d'espace, qu'un code à usage unique vient valider.
     */
    $withoutResolution = [
        'narrator.record.request_new_link',
        'narrator.space.request.show',
        'narrator.space.request.send',
        'narrator.space.request.verify',
    ];

    // Idem pour le limiteur : la route des événements du navigateur porte le
    // sien, plus large, sans quoi la mesure du taux d'échec serait étouffée.
    $withOwnThrottle = [
        'narrator.events.store' => 'throttle:client-events',
        // L'entrée de l'espace narrateur a ses propres limiteurs : bornés
        // sur la coordonnée demandée, et non sur l'IP seule, sans quoi une
        // maison de retraite serait enfermée dehors au deuxième résident.
        'narrator.space.request.send' => 'throttle:space-access',
        'narrator.space.request.verify' => 'throttle:space-verify',
    ];

    foreach ($routes as $route) {
        $middleware = collect($route->gatherMiddleware());
        $uri = (string) $route->uri();
        $expectedThrottle = $withOwnThrottle[$route->getName()] ?? 'throttle:tokens';

        expect($middleware->contains($expectedThrottle))->toBeTrue("{$uri} sans limiteur")
            ->and($middleware->contains('no-store'))->toBeTrue("{$uri} sans no-store");

        if (! in_array($route->getName(), $withoutResolution, true)) {
            $resolves = $middleware->contains(
                fn (mixed $name): bool => is_string($name) && str_starts_with($name, 'resolve.token:'),
            );

            expect($resolves)->toBeTrue("{$uri} ne résout pas son jeton");
        }
    }
});

it('révoque le lien d’enregistrement dès que l’histoire est validée', function (): void {
    $story = Story::factory()->toReview()->create();
    $issued = app(TokenService::class)->issue(TokenType::Record, $story);

    $this->get("/r/{$issued->plain}")->assertOk();

    $story->state->transitionTo(Validated::class);

    $this->get("/r/{$issued->plain}")
        ->assertStatus(410)
        ->assertInertia(fn ($page) => $page->where('reason', 'revoked'));

    expect(AccessToken::query()->active()->count())->toBe(0);
});

it('desserre la borne par IP hors production, jamais celle par jeton', function (): void {
    // La borne par jeton protège les liens : elle vaut la même chose partout.
    expect((int) config('product.security.rate_limits.tokens_per_token'))->toBe(20);

    // Celle par IP protège l'infrastructure et punit le partage de connexion :
    // en test, trente navigateurs partagent une adresse (T-79).
    $issued = [];

    for ($i = 0; $i < 8; $i++) {
        [$one] = issueRecordLink();
        $issued[] = $one;
    }

    // Huit jetons × dix requêtes = quatre-vingts : au-delà des soixante de
    // production, et sans 429 ici.
    foreach ($issued as $one) {
        for ($i = 0; $i < 10; $i++) {
            $this->get("/r/{$one->plain}")->assertOk();
        }
    }
});
