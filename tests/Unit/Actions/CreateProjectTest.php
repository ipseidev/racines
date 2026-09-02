<?php

declare(strict_types=1);

use App\Actions\AddFamilyMember;
use App\Actions\AddNarrator;
use App\Actions\CreateProject;
use App\Enums\Offer;
use App\Enums\ProjectMemberRole;
use App\Enums\ProjectStatus;
use App\Exceptions\Domain\NarratorNotReachable;
use App\Models\User;

it('crée le projet en brouillon et inscrit la propriétaire comme Initiateur·rice', function (): void {
    $owner = User::factory()->create();

    $project = app(CreateProject::class)->handle($owner, Offer::Pilot, ['prompt_day' => 3]);

    expect($project->status)->toBe(ProjectStatus::Draft)
        ->and($project->offer)->toBe(Offer::Pilot)
        ->and($project->owner_user_id)->toBe($owner->id)
        ->and($project->prompt_day)->toBe(3)
        ->and($project->members()->where('user_id', $owner->id)->value('role'))
        ->toBe(ProjectMemberRole::Initiator);
});

it('n’accepte pas un statut imposé de l’extérieur', function (): void {
    $owner = User::factory()->create();

    $project = app(CreateProject::class)->handle($owner, Offer::Core, ['status' => ProjectStatus::Active->value]);

    expect($project->status)->toBe(ProjectStatus::Draft);
});

it('fait du premier narrateur le narrateur principal, et pas des suivants', function (): void {
    $owner = User::factory()->create();
    $project = app(CreateProject::class)->handle($owner, Offer::Pilot, []);

    $first = app(AddNarrator::class)->handle($project, [
        'first_name' => 'Marie',
        'phone_e164' => '+33600000001',
    ]);

    $second = app(AddNarrator::class)->handle($project, [
        'first_name' => 'Henri',
        'phone_e164' => '+33600000002',
    ]);

    expect($first->is_primary)->toBeTrue()
        ->and($first->display_name)->toBe('Marie')
        ->and($second->is_primary)->toBeFalse();
});

it('refuse un narrateur qu’on ne peut joindre ni par SMS ni par courriel', function (): void {
    $owner = User::factory()->create();
    $project = app(CreateProject::class)->handle($owner, Offer::Pilot, []);

    expect(fn () => app(AddNarrator::class)->handle($project, ['first_name' => 'Marie']))
        ->toThrow(NarratorNotReachable::class);
});

it('inscrit un proche avec la trace de qui l’a invité', function (): void {
    $owner = User::factory()->create();
    $project = app(CreateProject::class)->handle($owner, Offer::Pilot, []);

    $member = app(AddFamilyMember::class)->handle($project, $owner, [
        'display_name' => 'Camille',
        'relationship' => 'Petite-fille',
        'email' => 'camille@example.test',
    ]);

    expect($member->invited_by_user_id)->toBe($owner->id)
        ->and($member->project_id)->toBe($project->id)
        ->and($member->invited_at)->not->toBeNull()
        ->and($member->can_contribute)->toBeFalse();
});
