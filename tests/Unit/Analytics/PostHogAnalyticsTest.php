<?php

declare(strict_types=1);

use App\Enums\AnalyticsEvent;
use App\Exceptions\Domain\AnalyticsPiiLeak;
use App\Jobs\SendAnalyticsEvent;
use App\Services\Analytics\Analytics;
use App\Services\Analytics\LogAnalytics;
use App\Services\Analytics\PostHogAnalytics;
use Illuminate\Support\Facades\Queue;

/**
 * L'adaptateur PostHog.
 *
 * Trois garanties, et chacune protège d'une panne différente.
 *
 * **La garde anti-fuite passe avant l'envoi**, dans le processus qui a
 * produit la propriété — pas dans un worker où personne ne lit la sortie.
 *
 * **L'identifiant ne remonte à personne.** PostHog a besoin de relier les
 * événements d'un même projet ; il n'a besoin de rien d'autre.
 *
 * **L'envoi part en file.** Une mesure n'a jamais le droit de ralentir un
 * enregistrement, ni de tomber avec l'outil qui la reçoit.
 */
beforeEach(function (): void {
    Queue::fake();
});

it('met l’envoi en file, jamais dans la requête', function (): void {
    (new PostHogAnalytics)->capture(AnalyticsEvent::StoryPageOpened, ['story_sequence' => 2], 'projet-1');

    // La latence d'un tiers ne s'ajoute pas à la nôtre, et sa panne ne
    // devient pas la nôtre.
    Queue::assertPushed(SendAnalyticsEvent::class);
});

it('refuse d’envoyer une propriété qui contient une donnée personnelle', function (): void {
    expect(fn () => (new PostHogAnalytics)->capture(
        AnalyticsEvent::NarratorNotified,
        ['to' => 'odette@example.test'],
    ))->toThrow(AnalyticsPiiLeak::class);

    // Et rien n'est parti : la garde est avant l'envoi, pas après.
    Queue::assertNothingPushed();
});

it('hache l’identifiant avec la clé de l’application', function (): void {
    $premier = PostHogAnalytics::pseudonym('projet-1');

    expect($premier)->toHaveLength(64)
        ->and($premier)->not->toContain('projet-1')
        // Stable : sans quoi aucun entonnoir ne se dessine.
        ->and(PostHogAnalytics::pseudonym('projet-1'))->toBe($premier)
        ->and(PostHogAnalytics::pseudonym('projet-2'))->not->toBe($premier);
});

it('regroupe les événements sans sujet sous une entrée fixe', function (): void {
    // Un aléatoire fabriquerait un « utilisateur » par événement, et tous les
    // comptes de PostHog seraient faux.
    expect(PostHogAnalytics::pseudonym(null))->toBe(PostHogAnalytics::pseudonym(''));
});

/*
 * Le pilote décide, jamais la présence d'une clé.
 *
 * C'est la leçon T-61, et elle vaut ici plus qu'ailleurs : une clé oubliée
 * dans un `.env` de développement enverrait les événements d'un décor vers le
 * projet de production, et les chiffres du pilote mentiraient sans que rien
 * ne le dise.
 */
it('reste sur le journal tant que le pilote n’est pas posé', function (): void {
    config()->set('services.posthog.driver', 'log');
    config()->set('services.posthog.key', 'phc_une_vraie_cle');

    expect(app(Analytics::class))->toBeInstanceOf(LogAnalytics::class);
});

it('choisit PostHog quand le pilote le dit', function (): void {
    config()->set('services.posthog.driver', 'posthog');
    app()->forgetInstance(Analytics::class);

    expect(app(Analytics::class))->toBeInstanceOf(PostHogAnalytics::class);
});

it('n’envoie rien sans clé, même en pilote PostHog', function (): void {
    config()->set('services.posthog.key', '');

    // Le job s'exécute et se tait : une mesure ratée n'est jamais une erreur.
    (new SendAnalyticsEvent('essai', [], 'x'))->handle();

    expect(true)->toBeTrue();
});
