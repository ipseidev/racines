<?php

declare(strict_types=1);

use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;

it('laisse la propriétaire voir et modifier son projet', function (): void {
    $owner = User::factory()->create();
    $project = Project::factory()->create(['owner_user_id' => $owner->id]);

    expect($owner->can('view', $project))->toBeTrue()
        ->and($owner->can('update', $project))->toBeTrue()
        ->and($owner->can('manageMembers', $project))->toBeTrue();
});

it('laisse l’éditeur désigné voir et modifier, mais pas gérer les membres', function (): void {
    $project = Project::factory()->create();
    $editor = User::factory()->create();

    ProjectMember::factory()->editor()->create([
        'project_id' => $project->id,
        'user_id' => $editor->id,
    ]);

    expect($editor->can('view', $project))->toBeTrue()
        ->and($editor->can('update', $project))->toBeTrue()
        ->and($editor->can('manageMembers', $project))->toBeFalse();
});

it('refuse le projet d’autrui à une autre Initiateur·rice', function (): void {
    $project = Project::factory()->create();
    $stranger = User::factory()->create();

    expect($stranger->can('view', $project))->toBeFalse()
        ->and($stranger->can('update', $project))->toBeFalse()
        ->and($stranger->can('manageMembers', $project))->toBeFalse();
});

it('laisse le support en lecture seule voir sans modifier', function (): void {
    $project = Project::factory()->create();
    $support = User::factory()->supportReadonly()->create();

    expect($support->can('view', $project))->toBeTrue()
        ->and($support->can('update', $project))->toBeFalse()
        ->and($support->can('manageMembers', $project))->toBeFalse();
});

it('laisse le support écrire quand il en a la permission', function (): void {
    $project = Project::factory()->create();
    $support = User::factory()->support()->create();

    expect($support->can('view', $project))->toBeTrue()
        ->and($support->can('update', $project))->toBeTrue();
});
