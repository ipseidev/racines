<?php

declare(strict_types=1);

namespace App\States\Story\Transitions;

use App\Enums\StoryVisibility;
use App\Exceptions\Domain\ForbiddenTransition;
use App\Models\Story;
use App\States\Story\Shared;
use Spatie\ModelStates\Transition;

/**
 * VALIDÉE → PARTAGÉE. Première transition qui rend une histoire visible des
 * proches, et elle refuse les histoires réservées au livre.
 */
final class ShareStory extends Transition
{
    public function __construct(private readonly Story $story) {}

    public function handle(): Story
    {
        if ($this->story->visibility === StoryVisibility::BookOnly) {
            throw ForbiddenTransition::guardFailed(
                $this->story->state->getValue(),
                'shared',
                'the narrator reserved this story for the printed book',
            );
        }

        $this->story->state = new Shared($this->story);
        $this->story->shared_at = now();
        $this->story->save();

        return $this->story;
    }
}
