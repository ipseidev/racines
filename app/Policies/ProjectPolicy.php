<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\ProjectMemberRole;
use App\Models\Project;
use App\Models\User;

/**
 * Autorisations des utilisateurs authentifiés sur un projet.
 *
 * Ne concerne que l'Initiateur·rice, l'éditeur désigné et le personnel : le
 * narrateur et les proches n'ont pas de compte et agiront par jeton (bloc 03).
 */
final class ProjectPolicy
{
    public function view(User $user, Project $project): bool
    {
        return $project->isMember($user) || $user->can('support.read');
    }

    public function update(User $user, Project $project): bool
    {
        return $project->isMember($user) || $user->can('support.write');
    }

    /**
     * Déléguer l'accès à un projet reste la décision de qui l'a acheté :
     * l'éditeur désigné ne désigne pas d'autres éditeurs.
     */
    public function manageMembers(User $user, Project $project): bool
    {
        if ($project->owner_user_id === $user->id) {
            return true;
        }

        return $project->hasRole($user, ProjectMemberRole::Initiator)
            || $user->can('support.write');
    }
}
