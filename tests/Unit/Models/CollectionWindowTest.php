<?php

declare(strict_types=1);

use App\Enums\Cadence;
use App\Enums\Offer;
use App\Models\Project;
use App\Models\Story;

/**
 * Ce que l'offre cœur vend : 52 questions, jamais une durée (R-2, v3.0).
 *
 * Le dossier vendait « 12 mois de collecte », ce qui donnait un contenu
 * différent selon le rythme au même prix : vingt-six histoires à qui reçoit
 * une question tous les quinze jours, cent quatre à qui en reçoit deux par
 * semaine. Le nombre est ce qu'on achète ; la durée en découle.
 */
it('étale les 52 questions sur le temps que le rythme demande', function (Cadence $cadence, int $semaines): void {
    expect(Project::collectionWeeks($cadence))->toBe($semaines);
})->with([
    // Une par semaine : l'année, et c'est le défaut.
    'hebdomadaire' => [Cadence::Weekly, 52],
    'deux fois par semaine' => [Cadence::TwiceWeekly, 26],
    // 52 ÷ 3 fait 17,33 : arrondi au-dessus, sans quoi la dernière question
    // tomberait hors de la fenêtre.
    'trois fois par semaine' => [Cadence::ThriceWeekly, 18],
    'tous les quinze jours' => [Cadence::Biweekly, 104],
]);

it('ouvre la fenêtre du projet au rythme du projet', function (): void {
    $project = Project::factory()->create([
        'offer' => Offer::Core,
        'cadence' => Cadence::TwiceWeekly,
    ]);

    $window = $project->collectionWindow(now());

    expect($window->collectionEndsAt->toDateString())
        ->toBe(now()->addWeeks(26)->toDateString())
        // Trois mois pour boucler le livre, après la dernière question.
        ->and($window->finalizationEndsAt->toDateString())
        ->toBe(now()->addWeeks(26)->addMonths(3)->toDateString());
});

it('laisse au pilote ses douze semaines, qui ne se comptent pas en questions', function (): void {
    $project = Project::factory()->create([
        'offer' => Offer::Pilot,
        'cadence' => Cadence::Biweekly,
    ]);

    $window = $project->collectionWindow(now());

    expect($window->collectionEndsAt->toDateString())
        ->toBe(now()->addWeeks((int) config('product.offer.pilot_weeks'))->toDateString());
});

/*
 * Le moteur propose de ralentir à qui peine (bloc 09). Si la fenêtre ne
 * suivait pas, accepter son aide reviendrait à renoncer à la moitié des
 * questions achetées — et la proposition deviendrait un piège.
 */
it('allonge la fenêtre quand le rythme ralentit, pour les questions qui restent', function (): void {
    $project = Project::factory()->create([
        'offer' => Offer::Core,
        'cadence' => Cadence::Weekly,
    ])->startCollection(now()->subWeeks(10));

    Story::factory()->count(10)->sequence(
        ...array_map(fn (int $n): array => ['sequence' => $n], range(1, 10)),
    )->create(['project_id' => $project->id]);

    $project->cadence = Cadence::Biweekly;
    $project->save();
    $project->rescheduleWindowForCadence();

    // Quarante-deux questions restantes, une tous les quinze jours : 84
    // semaines à partir d'aujourd'hui, et non la fin du calendrier initial.
    expect($project->collection_ends_at?->toDateString())
        ->toBe(now()->addWeeks(84)->toDateString());
});

it('raccourcit la fenêtre quand le rythme accélère', function (): void {
    $project = Project::factory()->create([
        'offer' => Offer::Core,
        'cadence' => Cadence::Weekly,
    ])->startCollection(now());

    $project->cadence = Cadence::ThriceWeekly;
    $project->save();
    $project->rescheduleWindowForCadence();

    // Accélérer, c'est recevoir ses questions plus tôt, pas en recevoir plus.
    expect($project->collection_ends_at?->toDateString())
        ->toBe(now()->addWeeks(18)->toDateString());
});

it('ne touche pas à la fenêtre du pilote, qui se vend en semaines', function (): void {
    $project = Project::factory()->create([
        'offer' => Offer::Pilot,
        'cadence' => Cadence::Weekly,
    ])->startCollection(now());

    $avant = $project->collection_ends_at?->toDateString();

    $project->cadence = Cadence::Biweekly;
    $project->save();
    $project->rescheduleWindowForCadence();

    expect($project->fresh()?->collection_ends_at?->toDateString())->toBe($avant);
});
