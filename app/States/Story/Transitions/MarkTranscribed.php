<?php

declare(strict_types=1);

namespace App\States\Story\Transitions;

use App\Models\Story;
use App\States\Story\Transcribed;
use Spatie\ModelStates\Transition;

/**
 * ENREGISTRÉE → TRANSCRITE. Le verbatim existe ; rien n'est encore visible.
 */
final class MarkTranscribed extends Transition
{
    public function __construct(private readonly Story $story) {}

    public function handle(): Story
    {
        $this->story->state = new Transcribed($this->story);
        $this->story->transcribed_at = now();
        $this->story->save();

        return $this->story;
    }
}
