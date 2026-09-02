<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\DeletionRequestedBy;
use App\Jobs\PurgeDeletedStory;
use App\Models\Story;
use App\States\Story\Deleted;
use App\States\Story\Trashed;
use Illuminate\Support\Facades\Log;

/**
 * Supprime pour de bon : l'état passe à `deleted` et le contenu part.
 *
 * Le seul acte irréversible du produit, et le seul qui efface des objets du
 * stockage. Trois précautions l'entourent : il faut passer par la corbeille,
 * exhiber une autorisation d'acte sensible, et taper le mot SUPPRIMER.
 *
 * La ligne `stories`, elle, reste : sans elle, on ne saurait plus qu'une
 * histoire a existé ni qu'elle a été supprimée — et une famille qui demande
 * « où est passé le récit de maman ? » mérite une réponse.
 */
final readonly class DeleteStoryAction
{
    public function handle(Story $story, DeletionRequestedBy $by): Story
    {
        if ($story->state instanceof Deleted) {
            return $story;
        }

        if (! $story->state instanceof Trashed) {
            // On passe par la corbeille : c'est là que vivent les trente
            // jours de rétractation.
            $story->state->transitionTo(Trashed::class);
        }

        $story->deletion_requested_by = $by;
        $story->save();

        $story->state->transitionTo(Deleted::class);

        Log::warning('story.deleted', [
            'story_id' => $story->id,
            'requested_by' => $by->value,
        ]);

        PurgeDeletedStory::dispatch($story->id);

        return $story;
    }
}
