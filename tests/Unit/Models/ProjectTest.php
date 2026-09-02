<?php

declare(strict_types=1);

use App\Enums\Offer;
use App\Models\Narrator;
use App\Models\Project;
use Illuminate\Database\QueryException;

it('has exactly one primary narrator', function (): void {
    $project = Project::factory()->create();

    $primary = Narrator::factory()->primary()->create(['project_id' => $project->id]);
    Narrator::factory()->create(['project_id' => $project->id]);

    expect($project->primaryNarrator()->first()?->id)->toBe($primary->id)
        ->and($project->narrators()->count())->toBe(2);

    expect(fn () => Narrator::factory()->primary()->create(['project_id' => $project->id]))
        ->toThrow(QueryException::class);
});

it('computes collection and finalization windows for pilot and core offers', function (): void {
    $this->freezeTime();

    $pilot = Project::factory()->create(['offer' => Offer::Pilot]);
    $window = $pilot->collectionWindow(now());

    expect($window->collectionEndsAt->toDateString())->toBe(now()->addWeeks(12)->toDateString())
        ->and($window->finalizationEndsAt->toDateString())->toBe($window->collectionEndsAt->toDateString());

    $core = Project::factory()->core()->create();
    $window = $core->collectionWindow(now());

    expect($window->collectionEndsAt->toDateString())->toBe(now()->addMonths(12)->toDateString())
        ->and($window->finalizationEndsAt->toDateString())->toBe(now()->addMonths(15)->toDateString());
});

it('fige les trois échéances quand la collecte s’ouvre', function (): void {
    $this->freezeTime();

    $project = Project::factory()->core()->create()->startCollection();

    expect($project->collection_started_at?->toDateString())->toBe(now()->toDateString())
        ->and($project->collection_ends_at?->toDateString())->toBe(now()->addMonths(12)->toDateString())
        ->and($project->finalization_ends_at?->toDateString())->toBe(now()->addMonths(15)->toDateString());
});

it('reprend la date d’ouverture déjà enregistrée pour calculer la fenêtre', function (): void {
    $project = Project::factory()->create(['collection_started_at' => now()->subWeeks(4)]);

    expect($project->collectionWindow()->collectionEndsAt->toDateString())
        ->toBe(now()->subWeeks(4)->addWeeks(12)->toDateString());
});
