<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\Domain\ForbiddenTransition;
use App\Models\Story;
use App\States\Story\Hidden;
use Illuminate\Support\Facades\Log;

/**
 * Remet une histoire masquée là d'où elle venait.
 *
 * Nulle part ailleurs : une histoire masquée alors qu'elle n'était pas encore
 * partagée ne doit pas ressortir partagée. C'est la garde de `UnhideStory`
 * qui le vérifie ; on lui donne juste la bonne cible.
 */
final class UnhideStoryAction
{
    public function handle(Story $story): Story
    {
        if (! $story->state instanceof Hidden) {
            return $story;
        }

        $target = $story->previousStateClass();

        if ($target === null) {
            throw ForbiddenTransition::guardFailed('hidden', 'unknown', 'no previous state was recorded');
        }

        $story->state->transitionTo($target);

        Log::info('story.unhidden', ['story_id' => $story->id, 'to' => $story->state->getValue()]);

        return $story;
    }
}
