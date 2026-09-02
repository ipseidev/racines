<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Story;
use App\States\Story\Trashed;
use Illuminate\Support\Facades\Log;

/**
 * Met une histoire à la corbeille : réversible trente jours, puis supprimée.
 *
 * Rien n'est effacé du stockage à ce moment-là. La corbeille est une intention
 * de suppression, pas la suppression : c'est ce délai qui rend le geste
 * pardonnable.
 */
final class TrashStoryAction
{
    public function handle(Story $story): Story
    {
        if ($story->state instanceof Trashed) {
            return $story;
        }

        $story->state->transitionTo(Trashed::class);

        Log::info('story.trashed', [
            'story_id' => $story->id,
            'from' => $story->previous_state,
            'restorable_until' => $story->trashed_at
                ?->addDays((int) config('product.stories.trash_retention_days'))
                ->toIso8601String(),
        ]);

        return $story;
    }
}
