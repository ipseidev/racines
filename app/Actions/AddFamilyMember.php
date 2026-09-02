<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\FamilyMember;
use App\Models\Project;
use App\Models\User;

/**
 * Ajoute un proche au cercle d'écoute.
 *
 * On garde qui a invité qui : c'est ce qui permet de répondre à « pourquoi
 * cette personne a-t-elle accès à ces histoires ? ». L'ajout ne rend rien
 * visible : la visibilité dépend de l'état de chaque histoire (R-4).
 */
final class AddFamilyMember
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(Project $project, User $invitedBy, array $attributes): FamilyMember
    {
        $member = new FamilyMember($attributes);
        $member->project()->associate($project);
        $member->invitedBy()->associate($invitedBy);
        $member->invited_at ??= now();
        $member->save();

        return $member;
    }
}
