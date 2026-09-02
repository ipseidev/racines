<?php

declare(strict_types=1);

use App\Models\ProjectMember;
use App\Models\Story;
use App\Models\User;

it('laisse la propriétaire du projet voir et corriger le texte', function (): void {
    $story = Story::factory()->transcribed()->create();
    $owner = $story->project->owner;

    expect($owner->can('view', $story))->toBeTrue()
        ->and($owner->can('editText', $story))->toBeTrue();
});

it('refuse à l’Initiateur·rice de changer la visibilité, qui appartient au narrateur', function (): void {
    $story = Story::factory()->validated()->create();
    $owner = $story->project->owner;

    expect($owner->can('manageVisibility', $story))->toBeFalse();
});

it('refuse une histoire à un utilisateur étranger au projet', function (): void {
    $story = Story::factory()->shared()->create();
    $stranger = User::factory()->create();

    expect($stranger->can('view', $story))->toBeFalse()
        ->and($stranger->can('editText', $story))->toBeFalse();
});

it('laisse l’éditeur désigné corriger le texte', function (): void {
    $story = Story::factory()->transcribed()->create();
    $editor = User::factory()->create();

    ProjectMember::factory()->editor()->create([
        'project_id' => $story->project_id,
        'user_id' => $editor->id,
    ]);

    expect($editor->can('view', $story))->toBeTrue()
        ->and($editor->can('editText', $story))->toBeTrue();
});

it('refuse toute correction d’une histoire supprimée, même au support', function (): void {
    $story = Story::factory()->deleted()->create();
    $owner = $story->project->owner;
    $support = User::factory()->support()->create();
    $admin = User::factory()->admin()->create();

    foreach ([$owner, $support, $admin] as $user) {
        expect($user->can('view', $story))->toBeFalse()
            ->and($user->can('editText', $story))->toBeFalse()
            ->and($user->can('manageVisibility', $story))->toBeFalse();
    }
});

it('refuse la correction du texte au support en lecture seule', function (): void {
    $story = Story::factory()->transcribed()->create();
    $support = User::factory()->supportReadonly()->create();

    expect($support->can('view', $story))->toBeTrue()
        ->and($support->can('editText', $story))->toBeFalse();
});
