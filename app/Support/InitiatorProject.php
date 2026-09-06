<?php

declare(strict_types=1);

namespace App\Support;

use App\Exceptions\Domain\NoInitiatorProject;
use App\Models\Project;
use App\Models\User;

/**
 * Le projet de l'Initiateur·rice qui consulte.
 *
 * Au pilote, une personne porte un projet : on prend le plus récent qu'elle
 * possède. La requête est écrite ici et pas dans cinq contrôleurs, pour la
 * même raison que `VisibleStoriesForFamilyMember` au bloc 08 — une seconde
 * requête, écrite plus tard, oublierait le filtre sur le propriétaire.
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
        return Project::query()
            ->where('owner_user_id', $user->id)
            ->with(['primaryNarrator'])
            ->latest()
            ->first();
    }

    /**
     * Vérifie qu'un projet appartient bien à cette personne.
     */
    public static function assertOwned(Project $project, User $user): void
    {
        abort_unless($project->owner_user_id === $user->id, 404);
    }
}
