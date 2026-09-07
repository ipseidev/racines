<?php

declare(strict_types=1);

use App\Actions\IssueRecordToken;
use App\Enums\TokenIssuedReason;
use App\Models\Story;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Aucune mesure sur les pages à jeton.
 *
 * C'est la règle la plus stricte du bloc, et elle vient du dossier (§12) : un
 * narrateur de quatre-vingt-cinq ans n'a pas de compte, n'a rien accepté, et
 * ne sait pas ce qu'est un traceur. Aucun tiers ne regarde par-dessus son
 * épaule pendant qu'il raconte sa vie.
 *
 * La garde est **au serveur**, pas au front : la page ne reçoit pas de clé.
 * Une clé absente ne peut pas être utilisée par erreur, ce qui vaut mieux que
 * de la fournir avec la consigne de ne pas s'en servir.
 */
beforeEach(function (): void {
    config()->set('services.posthog.driver', 'posthog');
    config()->set('services.posthog.key', 'phc_de_test');
});

it('ne donne aucune clé à une page d’enregistrement', function (): void {
    $story = Story::factory()->toReview()->create();
    $issued = app(IssueRecordToken::class)->handle($story, TokenIssuedReason::Rotation);

    $this->get("/r/{$issued->plain}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('analytics', null));
});

it('n’en donne à aucun des huit espaces à jeton', function (string $prefixe): void {
    // Le préfixe suffit : la page peut répondre 404, ce qui compte est
    // qu'aucune clé ne parte avec la réponse.
    $reponse = $this->get("/{$prefixe}/".str_repeat('a', 43));

    expect($reponse->getContent())->not->toContain('phc_de_test');
})->with(['r', 'l', 'q', 'n', 'i', 'a', 'x']);

/*
 * Ni clé, **ni fragment**.
 *
 * L'inactivité ne suffisait pas : un paquet embarqué dans le lot principal
 * serait téléchargé par la page d'un narrateur même sans être démarré. Le
 * chargement dynamique le range dans un fragment à part, et cette page ne le
 * demande jamais — ni par un script, ni par un lien de préchargement.
 */
it('ne fait même pas télécharger le paquet de mesure', function (): void {
    $story = Story::factory()->toReview()->create();
    $issued = app(IssueRecordToken::class)->handle($story, TokenIssuedReason::Rotation);

    $reponse = $this->get("/r/{$issued->plain}");

    expect(mb_strtolower((string) $reponse->getContent()))->not->toContain('posthog')
        ->and(mb_strtolower((string) $reponse->headers->get('link', '')))->not->toContain('posthog');
});

it('en donne à la page d’accueil', function (): void {
    $this->get('/')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('analytics.key', 'phc_de_test')
            // Cloud UE, et jamais l'hôte par défaut du fournisseur.
            ->where('analytics.host', 'https://eu.i.posthog.com'));
});

it('n’en donne à personne tant que le pilote n’est pas posé', function (): void {
    config()->set('services.posthog.driver', 'log');

    // La clé peut traîner dans un `.env` de développement : elle ne suffit
    // pas à activer l'envoi (T-61).
    $this->get('/')->assertInertia(fn ($page) => $page->where('analytics', null));
});

/*
 * Google Analytics : la même règle, le même verrou, les mêmes tests.
 *
 * Un second fournisseur est exactement le moment où une garde se perd : la
 * première a été écrite en pensant à elle, la seconde arrive plus tard et
 * personne ne relit la liste des espaces. `App\Analytics\Measured` porte
 * désormais cette liste pour les deux, et ces cas le vérifient depuis
 * l'extérieur.
 */
it('ne donne aucun identifiant Google à une page d’enregistrement', function (): void {
    config()->set('services.google_analytics.enabled', true);
    config()->set('services.google_analytics.measurement_id', 'G-DETEST0001');

    $story = Story::factory()->toReview()->create();
    $issued = app(IssueRecordToken::class)->handle($story, TokenIssuedReason::Rotation);

    $reponse = $this->get("/r/{$issued->plain}")->assertOk();

    $reponse->assertInertia(fn ($page) => $page->where('googleAnalytics', null));

    // Ni dans les props, ni ailleurs dans la page : `gtag.js` ne peut pas
    // être demandé sans identifiant, et l'identifiant n'est pas là.
    expect($reponse->getContent())->not->toContain('G-DETEST0001')
        ->and(mb_strtolower((string) $reponse->getContent()))->not->toContain('googletagmanager');
});

it('n’en donne à aucun des espaces à jeton', function (string $prefixe): void {
    config()->set('services.google_analytics.enabled', true);
    config()->set('services.google_analytics.measurement_id', 'G-DETEST0001');

    $reponse = $this->get("/{$prefixe}/".str_repeat('a', 43));

    expect($reponse->getContent())->not->toContain('G-DETEST0001');
})->with(['r', 'l', 'q', 'n', 'i', 'a', 'x']);

it('donne l’identifiant Google à la page d’accueil', function (): void {
    config()->set('services.google_analytics.enabled', true);
    config()->set('services.google_analytics.measurement_id', 'G-DETEST0001');

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('googleAnalytics.measurementId', 'G-DETEST0001'));
});

it('n’en donne à personne tant que GA_ENABLED n’est pas posé', function (): void {
    // L'identifiant peut vivre dans un `.env` de développement : il ne suffit
    // pas à activer la mesure, sinon les visites d'un décor partiraient dans
    // la propriété du site (T-61).
    config()->set('services.google_analytics.enabled', false);
    config()->set('services.google_analytics.measurement_id', 'G-DETEST0001');

    $this->get('/')->assertInertia(fn ($page) => $page->where('googleAnalytics', null));
});

it('n’en donne pas non plus quand l’identifiant manque', function (): void {
    config()->set('services.google_analytics.enabled', true);
    config()->set('services.google_analytics.measurement_id', '');

    $this->get('/')->assertInertia(fn ($page) => $page->where('googleAnalytics', null));
});
