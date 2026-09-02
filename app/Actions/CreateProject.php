<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\Offer;
use App\Enums\ProjectMemberRole;
use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Crée un projet et y inscrit son Initiateur·rice.
 *
 * Le projet naît en brouillon : rien n'est envoyé à personne avant que
 * l'invitation du narrateur soit préparée (bloc 10). Le statut et l'offre ne
 * viennent jamais des attributs reçus : ils sont décidés ici.
 */
final class CreateProject
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(User $owner, Offer $offer, array $attributes = []): Project
    {
        return DB::transaction(function () use ($owner, $offer, $attributes): Project {
            $project = new Project($attributes);
            $project->owner()->associate($owner);
            $project->offer = $offer;
            $project->status = ProjectStatus::Draft;
            $project->save();

            $project->members()->create([
                'user_id' => $owner->id,
                'role' => ProjectMemberRole::Initiator,
            ]);

            return $project;
        });
    }
}
