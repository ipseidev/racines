<?php

declare(strict_types=1);

use App\Actions\AcceptInvitation;
use App\Actions\ApplyShareDecision;
use App\Enums\ConsentKind;
use App\Models\Consent;
use App\Models\Narrator;
use App\Models\Project;
use App\Models\Story;
use App\States\Story\Shared;
use App\States\Story\ToReview;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Le partage déclaré d'avance (D-10).
 *
 * Chaque tap est une occasion d'abandonner, et l'abandon est la plainte n°1 de
 * la catégorie. Le narrateur peut donc déclarer **une fois**, à l'acceptation,
 * que ses histoires seront partagées dès qu'elles sont prêtes.
 *
 * Ce n'est pas de la validation tacite, et la nuance est tout le sujet : un
 * silence ne vaut jamais accord, mais **un accord donné une fois n'est pas un
 * silence**. La déclaration est un consentement au sens de doc 04 §1 —
 * horodaté, tracé, révocable — et jamais pré-coché. R-4 a été précisé en
 * conséquence, et la décision est ouverte en R-12.
 *
 * Ce que ces tests protègent surtout : **l'absence de déclaration ne partage
 * rien**. C'est la garde qui doit survivre à toutes les refontes à venir.
 */
function transcribedStoryFor(Project $project): Story
{
    return Story::factory()->forProject($project)->transcribed()->create();
}

function projetAccepte(bool $declare): Project
{
    $project = Project::factory()->draft()->create();
    Narrator::factory()->create(['project_id' => $project->id, 'is_primary' => true]);
    $project->refresh();

    app(AcceptInvitation::class)->handle($project, [
        'declared_sharing' => $declare,
    ]);

    return $project->refresh();
}

it('n’enregistre aucun consentement de partage déclaré quand la case n’est pas cochée', function (): void {
    $project = projetAccepte(false);

    expect(Consent::query()
        ->where('project_id', $project->id)
        ->where('kind', ConsentKind::DeclaredSharing)
        ->exists())->toBeFalse()
        ->and($project->declared_sharing_at)->toBeNull();
});

it('trace le consentement et la date quand elle est cochée', function (): void {
    $project = projetAccepte(true);

    $consent = Consent::query()
        ->where('project_id', $project->id)
        ->where('kind', ConsentKind::DeclaredSharing)
        ->first();

    expect($consent)->not->toBeNull()
        ->and($consent->granted_at)->not->toBeNull()
        ->and($project->declared_sharing_at)->not->toBeNull();
});

/*
 * Le cœur de D-10 : sans décision par histoire, la déclaration prend le
 * relais. C'est ce qui supprime le tap.
 */
it('partage une histoire sans décision quand le partage est déclaré', function (): void {
    $project = projetAccepte(true);
    $story = transcribedStoryFor($project);

    app(ApplyShareDecision::class)->handle($story);

    expect($story->refresh()->state)->toBeInstanceOf(Shared::class)
        ->and($story->validated_at)->not->toBeNull();
});

/*
 * Et la garde qui ne doit jamais tomber : sans déclaration, le silence ne
 * partage rien. On demande une relecture, comme avant D-10.
 */
it('ne partage rien sans décision ni déclaration', function (): void {
    $project = projetAccepte(false);
    $story = transcribedStoryFor($project);

    app(ApplyShareDecision::class)->handle($story);

    expect($story->refresh()->state)->toBeInstanceOf(ToReview::class)
        ->and($story->shared_at)->toBeNull();
});
