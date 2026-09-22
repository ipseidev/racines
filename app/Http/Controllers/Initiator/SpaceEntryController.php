<?php

declare(strict_types=1);

namespace App\Http\Controllers\Initiator;

use App\Exceptions\Domain\NoInitiatorProject;
use App\Models\Project;
use App\Support\InitiatorProject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * La porte de l'espace : `/espace`.
 *
 * Elle n'affiche rien. Les pages portent désormais l'identifiant du projet
 * (`/espace/projets/{projet}/questions`), et cette route mène à celui du
 * compte — sans ajouter de clic à la quasi-totalité qui n'en a qu'un.
 *
 * Elle existe pour trois raisons qui valent mieux qu'une redirection écrite
 * à la main dans cinq endroits. Un signet sur `/espace` continue de
 * fonctionner. Un courriel du produit peut pointer là sans connaître de
 * projet. Et le jour où une page d'accueil listant les projets se justifie,
 * elle prend la place de cette redirection sans toucher à rien d'autre.
 *
 * Le projet retenu reste **le plus récent**, comme avant : ce qui change est
 * qu'il n'est plus le seul atteignable. Les autres vivent à leur adresse, et
 * le sélecteur de l'en-tête les montre.
 */
final class SpaceEntryController
{
    public function __invoke(Request $request): RedirectResponse
    {
        $user = $request->user();

        $project = $user === null ? null : InitiatorProject::latestOf($user);

        if (! $project instanceof Project) {
            // Pas une erreur : quelqu'un dont la commande n'est pas encore
            // honorée n'a rien cassé. Le gestionnaire d'exceptions sert la
            // page qui l'explique (T-199).
            throw NoInitiatorProject::make();
        }

        return redirect()->route('initiator.dashboard', ['project' => $project]);
    }

    /**
     * Une ancienne adresse d'onglet, menée au même onglet du projet courant.
     *
     * `/espace/questions` a vécu jusqu'au 21 septembre 2026 : elle est dans
     * des signets et dans l'historique. La rediriger coûte six lignes et
     * évite qu'un rangement d'URL passe pour une panne.
     */
    public function legacy(Request $request): RedirectResponse
    {
        $user = $request->user();
        $page = (string) $request->route()?->defaults['page'];

        $project = $user === null ? null : InitiatorProject::latestOf($user);

        if (! $project instanceof Project) {
            throw NoInitiatorProject::make();
        }

        return redirect()->to('/espace/projets/'.$project->id.'/'.$page);
    }
}
