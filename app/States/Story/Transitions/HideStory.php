<?php

declare(strict_types=1);

namespace App\States\Story\Transitions;

use App\Models\Story;
use App\States\Story\Hidden;
use Spatie\ModelStates\Transition;

/**
 * → MASQUÉE. Retrait réversible : l'état d'avant est mémorisé, rien n'est
 * supprimé, et l'audio source reste intact (R-4, doc 04 §3).
 */
final class HideStory extends Transition
{
    public function __construct(private readonly Story $story) {}

    public function handle(): Story
    {
        $this->story->previous_state = $this->story->state->getValue();
        $this->story->state = new Hidden($this->story);
        $this->story->hidden_at = now();
        $this->story->save();

        return $this->story;
    }
}
