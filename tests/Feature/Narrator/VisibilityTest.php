<?php

declare(strict_types=1);

use App\Actions\SetStoryVisibility;
use App\Enums\StoryVisibility;
use App\Models\FamilyMember;
use App\Models\Story;

/**
 * Une histoire partagée, et trois proches dans le projet.
 *
 * @return array{Story, list<FamilyMember>}
 */
function sharedStoryWithFamily(int $count = 3): array
{
    $story = Story::factory()->shared()->create();
    $members = FamilyMember::factory()
        ->count($count)
        ->create(['project_id' => $story->project_id])
        ->all();

    return [$story, $members];
}

it('ouvre l’histoire à tous les proches par défaut', function (): void {
    [$story, $members] = sharedStoryWithFamily();

    expect($story->visibility)->toBe(StoryVisibility::AllFamily);

    foreach ($members as $member) {
        expect($story->isVisibleTo($member))->toBeTrue();
    }
});

it('restreint l’écoute à une liste de proches', function (): void {
    [$story, $members] = sharedStoryWithFamily();
    [$allowed, $excluded, $alsoExcluded] = $members;

    app(SetStoryVisibility::class)->handle($story, StoryVisibility::Restricted, [$allowed->id]);

    $story->refresh();

    expect($story->visibility)->toBe(StoryVisibility::Restricted)
        ->and($story->isVisibleTo($allowed))->toBeTrue()
        ->and($story->isVisibleTo($excluded))->toBeFalse()
        ->and($story->isVisibleTo($alsoExcluded))->toBeFalse()
        // Le fil famille reste visible en tant qu'histoire partagée : c'est
        // le public qui se réduit, pas l'état.
        ->and($story->isVisibleToFamily())->toBeTrue();
});

it('retire l’accès immédiatement quand la liste se resserre', function (): void {
    [$story, $members] = sharedStoryWithFamily();
    [$first, $second] = $members;

    app(SetStoryVisibility::class)->handle($story, StoryVisibility::Restricted, [$first->id, $second->id]);
    expect($story->refresh()->isVisibleTo($second))->toBeTrue();

    app(SetStoryVisibility::class)->handle($story, StoryVisibility::Restricted, [$first->id]);

    // Sans effet immédiat, un narrateur qui retire un accès croirait l'avoir
    // retiré alors que le proche écoute encore.
    expect($story->refresh()->isVisibleTo($second))->toBeFalse();
});

it('rouvre à tous et oublie la liste', function (): void {
    [$story, $members] = sharedStoryWithFamily();

    app(SetStoryVisibility::class)->handle($story, StoryVisibility::Restricted, [$members[0]->id]);
    app(SetStoryVisibility::class)->handle($story, StoryVisibility::AllFamily, []);

    $story->refresh();

    expect($story->visibility)->toBe(StoryVisibility::AllFamily)
        ->and($story->allowedFamilyMembers()->count())->toBe(0)
        ->and($story->isVisibleTo($members[1]))->toBeTrue();
});

it('réserve au livre : personne n’écoute en ligne', function (): void {
    [$story, $members] = sharedStoryWithFamily();

    app(SetStoryVisibility::class)->handle($story, StoryVisibility::BookOnly, []);

    $story->refresh();

    // Le narrateur a choisi le papier, pas la diffusion.
    expect($story->isVisibleToFamily())->toBeFalse();

    foreach ($members as $member) {
        expect($story->isVisibleTo($member))->toBeFalse();
    }
});

it('n’accepte pas un proche d’un autre projet', function (): void {
    [$story] = sharedStoryWithFamily(0);
    $stranger = FamilyMember::factory()->create();

    app(SetStoryVisibility::class)->handle($story, StoryVisibility::Restricted, [$stranger->id]);

    // Une liste blanche qui accepte n'importe quel identifiant n'est pas une
    // liste blanche.
    expect($story->refresh()->isVisibleTo($stranger))->toBeFalse()
        ->and($story->allowedFamilyMembers()->count())->toBe(0);
});

it('refuse la restriction sans aucun proche désigné', function (): void {
    [$story] = sharedStoryWithFamily();

    // « Restreint à personne » est ambigu : c'est « garder pour moi », et ça
    // se dit autrement. On refuse plutôt que de deviner.
    app(SetStoryVisibility::class)->handle($story, StoryVisibility::Restricted, []);
})->throws(InvalidArgumentException::class);

it('ne rend visible aucune histoire non partagée, quelle que soit la liste', function (): void {
    $story = Story::factory()->transcribed()->create();
    $member = FamilyMember::factory()->create(['project_id' => $story->project_id]);

    app(SetStoryVisibility::class)->handle($story, StoryVisibility::Restricted, [$member->id]);

    // La liste dit *qui* pourrait écouter ; l'état dit *si* on écoute.
    expect($story->refresh()->isVisibleTo($member))->toBeFalse();
});
