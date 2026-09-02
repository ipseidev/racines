<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Story;
use App\States\Story\Hidden;
use Illuminate\Support\Facades\Log;

/**
 * Masque une histoire : elle disparaît des proches, rien n'est supprimé.
 *
 * Le retrait le plus doux des cinq, et le seul qu'un narrateur puisse faire
 * depuis son lien d'enregistrement sans code. C'est voulu : quelqu'un qui
 * regrette ce qu'il vient de raconter doit pouvoir le retirer tout de suite,
 * en deux gestes, sans attendre un SMS.
 */
final class HideStoryAction
{
    public function handle(Story $story): Story
    {
        if ($story->state instanceof Hidden) {
            return $story;
        }

        $story->state->transitionTo(Hidden::class);

        Log::info('story.hidden', [
            'story_id' => $story->id,
            'from' => $story->previous_state,
        ]);

        return $story;
    }
}
