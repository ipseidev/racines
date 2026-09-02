<?php

declare(strict_types=1);

namespace App\States\Story\Transitions;

use App\Models\Story;
use App\States\Story\ToReview;
use Spatie\ModelStates\Transition;

/**
 * TRANSCRITE → À RELIRE (variante B). Le narrateur est invité à relire ; ce
 * n'est pas une validation et aucun délai ne la remplacera.
 */
final class RequestReview extends Transition
{
    public function __construct(private readonly Story $story) {}

    public function handle(): Story
    {
        $this->story->state = new ToReview($this->story);
        $this->story->save();

        return $this->story;
    }
}
