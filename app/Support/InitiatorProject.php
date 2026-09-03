<?php

declare(strict_types=1);

namespace App\Support;

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
    public static function forOrFail(User $user): Project
    {
        $project = self::for($user);

        abort_if($project === null, 404);

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
