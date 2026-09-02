<?php

declare(strict_types=1);

namespace App\States\Story\Transitions;

use App\Models\Story;
use App\States\Story\Trashed;
use Spatie\ModelStates\Transition;

/**
 * → CORBEILLE. Restaurable pendant trente jours (R-4) ; rien n'est effacé du
 * stockage avant l'état `deleted`.
 */
final class TrashStory extends Transition
{
    public function __construct(private readonly Story $story) {}

    public function handle(): Story
    {
        $this->story->previous_state = $this->story->state->getValue();
        $this->story->state = new Trashed($this->story);
        $this->story->trashed_at = now();
        $this->story->save();

        return $this->story;
    }
}
