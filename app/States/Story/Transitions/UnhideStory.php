<?php

declare(strict_types=1);

namespace App\States\Story\Transitions;

use App\Exceptions\Domain\ForbiddenTransition;
use App\Models\Story;
use App\States\Story\StoryState;
use Spatie\ModelStates\DefaultTransition;
use Spatie\ModelStates\State;

/**
 * MASQUÉE → l'état d'avant, et lui seul.
 *
 * Hérite de `DefaultTransition` pour connaître l'état visé : la garde compare
 * la cible à `previous_state`, ce qui interdit de sortir d'un masquage vers un
 * état plus avancé — notamment de passer de masquée à partagée sans repasser
 * par la validation.
 */
final class UnhideStory extends DefaultTransition
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
                'hidden',
                $target,
                'a hidden story only returns to the state it was hidden from ['.($this->story->previous_state ?? 'none').']',
            );
        }

        $this->story->state = $this->target;
        $this->story->previous_state = null;
        $this->story->hidden_at = null;
        $this->story->save();

        return $this->story;
    }
}
