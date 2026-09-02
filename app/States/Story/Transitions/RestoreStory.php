<?php

declare(strict_types=1);

namespace App\States\Story\Transitions;

use App\Exceptions\Domain\ForbiddenTransition;
use App\Models\Story;
use App\States\Story\StoryState;
use Spatie\ModelStates\DefaultTransition;
use Spatie\ModelStates\State;

/**
 * CORBEILLE → l'état d'avant, dans la fenêtre de trente jours.
 *
 * Passé le délai, la restauration est refusée : la corbeille n'est pas un
 * archivage déguisé.
 */
final class RestoreStory extends DefaultTransition
{
    private readonly Story $story;

    private readonly StoryState $target;

    /**
     * @param  State<Story>  $newState
     */
    public function __construct(Story $story, string $field, State $newState)
    {
        parent::__construct($story, $field, $newState);

        if (! $newState instanceof StoryState) {
            throw ForbiddenTransition::notAllowed($story->state->getValue(), $newState->getValue());
        }

        $this->story = $story;
        $this->target = $newState;
    }

    public function handle(): Story
    {
        $target = $this->target->getValue();

        if ($this->story->previous_state !== $target) {
            throw ForbiddenTransition::guardFailed(
                'trashed',
                $target,
                'a trashed story only returns to the state it was trashed from ['.($this->story->previous_state ?? 'none').']',
            );
        }

        $days = (int) config('product.stories.trash_retention_days');

        if ($this->story->trashed_at === null || $this->story->trashed_at->lt(now()->subDays($days))) {
            throw ForbiddenTransition::guardFailed(
                'trashed',
                $target,
                "the {$days}-day restore window has closed",
            );
        }

        $this->story->state = $this->target;
        $this->story->previous_state = null;
        $this->story->trashed_at = null;
        $this->story->save();

        return $this->story;
    }
}
