<?php

declare(strict_types=1);

namespace App\States\Story\Transitions;

use App\Enums\AnswerType;
use App\Models\Story;
use App\States\Story\Recorded;
use Spatie\ModelStates\Transition;

/**
 * PROPOSÉE → ENREGISTRÉE.
 *
 * Franchie seulement quand l'audio est confirmé côté stockage (bloc 04) : le
 * dossier interdit d'annoncer « histoire enregistrée » avant réplication.
 */
final class RecordStory extends Transition
{
    public function __construct(
        private readonly Story $story,
        private readonly AnswerType $answerType = AnswerType::Audio,
    ) {}

    public function handle(): Story
    {
        // La question telle qu'elle a été lue, avant que l'état ne change :
        // une fois la réponse donnée, reformuler le corpus ne doit plus
        // changer ce qu'on lui a demandé (voir `Story::questionText()`).
        if ($this->story->question_id !== null && $this->story->custom_question_text === null) {
            $this->story->question_text ??= $this->story->questionText();
        }

        $this->story->state = new Recorded($this->story);
        $this->story->answer_type = $this->answerType;
        $this->story->recorded_at = now();
        $this->story->save();

        return $this->story;
    }
}
