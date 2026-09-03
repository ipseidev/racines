<?php

declare(strict_types=1);

use App\Actions\AcceptInvitation;
use App\Enums\ConsentKind;
use App\Enums\ProjectStatus;
use App\Models\Project;
use Database\Seeders\E2ELinksSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Le décor doit avoir consenti, comme une vraie narratrice.
 *
 * Le défaut que ce test attrape est de ceux qui font perdre une soirée.
 * `RenderFluide` refuse de rendre un texte sans `ai_rendering`, et c'est la
 * bonne règle. Mais le décor créait ses narratrices par `AddNarrator`, sans
 * passer par l'opt-in qui accorde les cinq consentements : toute chaîne
 * réellement jouée en local s'arrêtait donc au mot à mot, en silence — un
 * simple `fluide.skipped_no_consent` dans le journal. La page de relecture
 * affichait « Alors euh je me souviens… » au lieu du texte mis au propre, et
 * la conclusion évidente pour un humain devant l'écran était « le rendu IA
 * est cassé ».
 *
 * En production, les cinq consentements sont posés ensemble à l'acceptation :
 * une narratrice qui reçoit des questions les a forcément. Le décor doit donc
 * les avoir aussi, sinon il ne représente aucun état atteignable.
 */
beforeEach(function (): void {
    $this->seed(E2ELinksSeeder::class);
});

it('accorde les cinq consentements de l’opt-in à toute narratrice d’un projet actif', function (): void {
    $projects = Project::query()
        ->where('status', ProjectStatus::Active)
        ->with('narrators')
        ->get();

    expect($projects)->not->toBeEmpty();

    $missing = [];

    foreach ($projects as $project) {
        foreach ($project->narrators as $narrator) {
            foreach (AcceptInvitation::CONSENTS as $kind) {
                if (! $narrator->hasConsent($kind)) {
                    $missing[] = "{$project->id} / {$narrator->id} : {$kind->value}";
                }
            }
        }
    }

    expect($missing)->toBe([]);
});

it('permet donc le rendu Fluide, qui est tout l’objet de la chaîne', function (): void {
    $narrators = Project::query()
        ->where('status', ProjectStatus::Active)
        ->with('narrators')
        ->get()
        ->flatMap(fn (Project $project) => $project->narrators);

    expect($narrators)->not->toBeEmpty()
        ->and($narrators->every(fn ($n): bool => $n->hasConsent(ConsentKind::AiRendering)))
        ->toBeTrue();
});
