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
 * rien**. C'est la garde qui doit survivre à toutes les refontes à venir — et
 * elle a déjà survécu à la plus grosse : depuis T-250, la déclaration est le
 * sixième accord, donnée par « J'accepte » et donc présente sur tout projet
 * neuf. Un projet sans déclaration existe encore, et c'est celui d'une
 * narratrice qui y a **mis fin** depuis son espace. Le code n'a pas le droit
 * de l'oublier : c'est exactement là que le silence redeviendrait un accord.
 */
function transcribedStoryFor(Project $project): Story
{
    return Story::factory()->forProject($project)->transcribed()->create();
}

function projetAccepte(bool $declare = true): Project
{
    $project = Project::factory()->draft()->create();
    Narrator::factory()->create(['project_id' => $project->id, 'is_primary' => true]);
    $project->refresh();

    app(AcceptInvitation::class)->handle($project, []);

    // Le cas d'une narratrice qui a arrêté l'envoi depuis son espace : c'est
    // le seul chemin, depuis T-250, vers un projet actif sans déclaration.
    if (! $declare) {
        $project->declared_sharing_at = null;
        $project->save();
    }

    return $project->refresh();
}

it('trace le consentement et la date dès l’acceptation, sans rien demander', function (): void {
    $project = projetAccepte();

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
    $project = projetAccepte();
    $story = transcribedStoryFor($project);

    app(ApplyShareDecision::class)->handle($story);

    expect($story->refresh()->state)->toBeInstanceOf(Shared::class)
        ->and($story->validated_at)->not->toBeNull();
});

/*
 * Et la garde qui ne doit jamais tomber : sans déclaration, le silence ne
 * partage rien. On demande une relecture, comme avant D-10.
 */
it('ne partage rien après un arrêt de l’envoi, décision ou pas', function (): void {
    $project = projetAccepte(declare: false);
    $story = transcribedStoryFor($project);

    app(ApplyShareDecision::class)->handle($story);

    expect($story->refresh()->state)->toBeInstanceOf(ToReview::class)
        ->and($story->shared_at)->toBeNull();
});
