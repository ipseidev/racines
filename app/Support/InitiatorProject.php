<?php

declare(strict_types=1);

namespace App\Support;

use App\Exceptions\Domain\NoInitiatorProject;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * Le projet de l'Initiateur·rice qui consulte.
 *
 * La requête est écrite ici et pas dans huit contrôleurs, pour la même raison
 * que `VisibleStoriesForFamilyMember` au bloc 08 — une seconde requête,
 * écrite plus tard, oublierait le filtre sur le propriétaire.
 *
 * **Le projet de l'adresse l'emporte** (21 septembre 2026). Les pages de
 * l'espace vivent sous `/espace/projets/{projet}/…` et la route est gardée
 * par `can:view,project`. Quand elle en porte un, c'est celui-là qu'on
 * regarde, sans discussion.
 *
 * Le repli sur « le plus récent » ne sert plus qu'à deux choses : la porte
 * `/espace`, qui redirige vers lui, et les rares appels hors de ces routes.
 * C'était la règle **partout** jusqu'ici, et elle avait un défaut silencieux
 * qu'un compte de démonstration a montré sans ambiguïté : trois projets, un
 * seul affiché, et l'invisible était le seul actif. Rien n'empêche d'offrir
 * à ses deux parents.
 *
 * Lire la route ici plutôt que de passer `Project` en argument est un
 * raccourci assumé : les vingt méthodes concernées résolvent déjà leur projet
 * par cet appel, et les migrer une à une peut se faire ensuite sans rien
 * casser — ce fichier restera juste dans les deux cas.
 */
final class InitiatorProject
{
    /**
     * Le projet, ou une page qui explique qu'il n'y en a pas encore.
     *
     * `NoInitiatorProject` et non `abort(404)` : l'absence de projet n'est
     * pas une erreur, et la trace technique de Laravel servie dans son propre
     * espace laisse croire qu'on a cassé quelque chose (T-199). Le
     * gestionnaire d'exceptions décide de la réponse une fois pour toutes les
     * pages, présentes et à venir.
     */
    public static function forOrFail(User $user): Project
    {
        $project = self::for($user);

        if ($project === null) {
            throw NoInitiatorProject::make();
        }

        return $project;
    }

    public static function for(User $user): ?Project
    {
        $bound = self::fromRoute();

        if ($bound instanceof Project) {
            return $bound;
        }

        return self::latestOf($user);
    }

    /**
     * Le projet nommé par l'adresse, s'il y en a une.
     *
     * L'instance vient de la liaison implicite de Laravel, qui se résout
     * **avant** le middleware : au moment où un contrôleur appelle, la
     * politique `view` est déjà passée. On ne revérifie donc pas ici, et on
     * ne le fait pas non plus par prudence : une garde recopiée à deux
     * endroits finit par diverger, et c'est la copie oubliée qui laisse
     * passer.
     */
    private static function fromRoute(): ?Project
    {
        $route = request()->route();

        if ($route === null) {
            return null;
        }

        $project = $route->parameter('project');

        return $project instanceof Project ? $project : null;
    }

    /** Le plus récent projet du compte : la porte `/espace` s'en sert. */
    public static function latestOf(User $user): ?Project
    {
        return Project::query()
            ->where('owner_user_id', $user->id)
            ->with(['primaryNarrator'])
            ->latest()
            ->first();
    }

    /**
     * Tous les projets du compte, pour le sélecteur de l'en-tête.
     *
     * @return Collection<int, Project>
     */
    public static function allOf(User $user): Collection
    {
        return Project::query()
            ->where('owner_user_id', $user->id)
            ->with(['primaryNarrator'])
            ->latest()
            ->get();
    }

    /**
     * Vérifie qu'un projet appartient bien à cette personne.
     */
    public static function assertOwned(Project $project, User $user): void
    {
        abort_unless($project->owner_user_id === $user->id, 404);
    }
}
