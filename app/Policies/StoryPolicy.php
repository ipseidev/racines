<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Story;
use App\Models\User;
use App\States\Story\Deleted;

/**
 * Autorisations des utilisateurs authentifiés sur une histoire.
 *
 * Deux règles portent tout le reste :
 *
 * 1. Une histoire supprimée n'est plus lisible par personne, pas même par
 *    l'administration. La trace de l'acte vit dans le journal d'audit
 *    (bloc 11), pas dans le contenu.
 * 2. La visibilité appartient au narrateur, jamais à l'Initiateur·rice.
 *    Celle-ci organise, achète, prépare le BAT ; elle ne décide pas de ce que
 *    la famille voit. Le narrateur agira par jeton (bloc 03) et par OTP pour
 *    les actes sensibles ; la personne mandatée arrive au bloc 07 ; le support
 *    n'agit que sur demande écrite, avec `support.write`.
 */
final class StoryPolicy
{
    public function view(User $user, Story $story): bool
    {
        if ($story->state instanceof Deleted) {
            return false;
        }

        return $story->project->isMember($user) || $user->can('support.read');
    }

    public function editText(User $user, Story $story): bool
    {
        if ($story->state instanceof Deleted) {
            return false;
        }

        return $story->project->isMember($user) || $user->can('transcripts.edit');
    }

    public function manageVisibility(User $user, Story $story): bool
    {
        if ($story->state instanceof Deleted) {
            return false;
        }

        return $user->can('support.write');
    }
}
