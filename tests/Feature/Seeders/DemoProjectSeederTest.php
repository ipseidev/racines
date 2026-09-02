<?php

declare(strict_types=1);

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\Story;
use App\Models\User;
use Database\Seeders\DemoProjectSeeder;

beforeEach(function (): void {
    $this->seed(DemoProjectSeeder::class);
});

it('produit cinq histoires dans cinq états distincts', function (): void {
    $states = Story::query()->pluck('state')->map(fn ($state): string => (string) $state)->all();

    expect($states)->toHaveCount(5)
        ->and(array_unique($states))->toHaveCount(5)
        ->and($states)->toEqualCanonicalizing(['proposed', 'recorded', 'to_review', 'shared', 'hidden']);
});

it('produit un projet actif, un narrateur principal et trois proches', function (): void {
    $project = Project::query()->sole();

    expect($project->status)->toBe(ProjectStatus::Active)
        ->and($project->narrators()->count())->toBe(1)
        ->and($project->primaryNarrator()->first()?->phone_e164)->toBe('+33600000000')
        ->and($project->familyMembers()->count())->toBe(3)
        ->and($project->members()->count())->toBe(1);
});

it('n’expose aux proches que l’histoire partagée', function (): void {
    $visible = Story::query()->get()->filter(fn (Story $story): bool => $story->isVisibleToFamily());

    expect($visible)->toHaveCount(1)
        ->and($visible->first()?->getRawOriginal('state'))->toBe('shared');
});

it('reste rejouable sans dupliquer le projet de démonstration', function (): void {
    $this->seed(DemoProjectSeeder::class);

    expect(Project::query()->count())->toBe(1)
        ->and(User::query()->where('email', 'demo@example.test')->count())->toBe(1)
        ->and(Story::query()->count())->toBe(5);
});
