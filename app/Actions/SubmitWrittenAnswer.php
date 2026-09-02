<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AnswerType;
use App\Models\Story;
use App\States\Story\Recorded;

/**
 * Enregistre une réponse écrite (P0-5).
 *
 * Elle emprunte la même machine d'états qu'une réponse orale : l'histoire
 * passe à `recorded`, sera relue et validée de la même façon. Ce n'est pas une
 * voie de garage.
 */
final class SubmitWrittenAnswer
{
    public function handle(Story $story, string $text): Story
    {
        $story->written_answer = trim($text);
        $story->save();

        if (! $story->state instanceof Recorded) {
            $story->state->transitionTo(Recorded::class, AnswerType::Text);
        }

        return $story;
    }
}
