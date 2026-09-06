<?php

declare(strict_types=1);

use App\Actions\DeleteStoryAction;
use App\Actions\HideStoryAction;
use App\Actions\IssueRefund;
use App\Analytics\PiiGuard;
use App\Enums\AnalyticsEvent;
use App\Enums\DeletionRequestedBy;
use App\Enums\ShareDecision;
use App\Enums\ValidatedVia;
use App\Models\Order;
use App\Models\Project;
use App\Models\Story;
use App\Services\Analytics\Analytics;
use App\Services\Analytics\LogAnalytics;
use App\Services\Payments\FakeRefunds;
use App\Services\Payments\Refunds;
use App\States\Story\Validated;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Les événements du funnel, vérifiés **en exécutant l'action réelle**.
 *
 * C'est tout l'objet de ce fichier, et la leçon la plus chère de la semaine :
 * un test qui appellerait `Analytics::capture()` à la main prouverait que la
 * façade sait enregistrer un événement, jamais que quelque chose le
 * déclenche. Quatre défauts sont sortis de là (T-154, T-178, T-181, T-191).
 *
 * Chaque cas ci-dessous part donc du geste métier — valider, masquer,
 * rembourser — et regarde ensuite ce qui a été mesuré.
 */
beforeEach(function (): void {
    $this->analytics = new LogAnalytics;
    app()->instance(Analytics::class, $this->analytics);
});

/** @return list<string> */
function evenementsEmis(LogAnalytics $analytics): array
{
    return array_map(
        fn (array $capture): string => $capture['event'],
        $analytics->captured(),
    );
}

/** @return array<string, mixed>|null */
function proprietesDe(LogAnalytics $analytics, AnalyticsEvent $event): ?array
{
    foreach ($analytics->captured() as $capture) {
        if ($capture['event'] === $event->value) {
            return $capture['properties'];
        }
    }

    return null;
}

it('mesure une validation, avec son rang', function (): void {
    $story = Story::factory()->transcribed()->create();
    $story->forceFill(['share_decision' => ShareDecision::Share])->save();

    $story->state->transitionTo(Validated::class, ValidatedVia::RecordingEnd);

    expect(evenementsEmis($this->analytics))
        ->toContain(AnalyticsEvent::StoryValidated->value)
        // La première franchit un palier du dossier : c'est là que les
        // familles s'arrêtent le plus.
        ->toContain(AnalyticsEvent::FirstStoryValidated->value);

    expect(proprietesDe($this->analytics, AnalyticsEvent::StoryValidated)['validated_rank'])->toBe(1);
});

it('marque le troisième et le dixième palier, et rien entre les deux', function (): void {
    $project = Project::factory()->create();

    for ($i = 1; $i <= 4; $i++) {
        $story = Story::factory()->transcribed()->create(['project_id' => $project->id]);
        $story->forceFill(['share_decision' => ShareDecision::Share])->save();
        $story->state->transitionTo(Validated::class, ValidatedVia::RecordingEnd);
    }

    $emis = evenementsEmis($this->analytics);

    expect($emis)->toContain(AnalyticsEvent::ThirdStoryValidated->value)
        ->and($emis)->not->toContain(AnalyticsEvent::TenthStoryValidated->value)
        // Un palier ne se franchit qu'une fois.
        ->and(count(array_filter($emis, fn (string $e): bool => $e === AnalyticsEvent::ThirdStoryValidated->value)))->toBe(1);
});

it('mesure un masquage comme une contre-métrique', function (): void {
    $story = Story::factory()->shared()->create();

    app(HideStoryAction::class)->handle($story);

    // Un taux de validation excellent accompagné de retraits massifs dirait
    // qu'on a poussé des gens à partager ce qu'ils ne voulaient pas.
    expect(evenementsEmis($this->analytics))->toContain(AnalyticsEvent::StoryHidden->value);
});

it('mesure une suppression', function (): void {
    $story = Story::factory()->shared()->create();

    app(DeleteStoryAction::class)->handle($story, DeletionRequestedBy::Narrator);

    expect(evenementsEmis($this->analytics))->toContain(AnalyticsEvent::StoryDeleted->value);
});

it('mesure un remboursement, et dit s’il est partiel', function (): void {
    app()->instance(Refunds::class, new FakeRefunds);

    // Avec son projet : une commande sans projet n'existe pas en production,
    // et `Track` consigne le trou plutôt que de se taire.
    $order = Order::factory()->paid()->create([
        'total_cents' => 8_900,
        'project_id' => Project::factory()->create()->id,
    ]);

    app(IssueRefund::class)->handle($order, 2_000, 'Geste commercial');

    $proprietes = proprietesDe($this->analytics, AnalyticsEvent::RefundIssued);

    // Le seuil de H3 est « remboursements ≤ 8 % » : un remboursement partiel
    // et un remboursement total n'y pèsent pas pareil.
    expect($proprietes)->not->toBeNull()
        ->and($proprietes['partial'])->toBeTrue();
});

it('accompagne chaque mesure de la cohorte et de la variante', function (): void {
    $story = Story::factory()->shared()->create();

    app(HideStoryAction::class)->handle($story);

    $proprietes = proprietesDe($this->analytics, AnalyticsEvent::StoryHidden);

    // Sans elles, l'entonnoir ne se découpe pas — et le pilote se lit par
    // cohorte, deux vagues de familles n'ayant pas reçu le même produit.
    expect($proprietes)->toHaveKeys(['project_id', 'cohort', 'offer', 'validation_variant']);
});

it('n’émet jamais une propriété que la garde refuserait', function (): void {
    // Deux histoires : une masquée ne peut pas aller à la corbeille, et
    // enchaîner les deux gestes sur la même testerait la machine à états,
    // pas la mesure.
    app(HideStoryAction::class)->handle(Story::factory()->shared()->create());
    app(DeleteStoryAction::class)->handle(Story::factory()->shared()->create(), DeletionRequestedBy::Narrator);

    // La garde tourne dans l'adaptateur PostHog ; ici on vérifie que les
    // propriétés produites par le code métier lui conviennent, sans quoi la
    // mesure exploserait le jour du basculement en production.
    foreach ($this->analytics->captured() as $capture) {
        PiiGuard::assertClean($capture['properties']);
    }

    expect(true)->toBeTrue();
});
