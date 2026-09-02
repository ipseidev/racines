<?php

declare(strict_types=1);

namespace App\States\Story\Transitions;

use App\Enums\ConsentKind;
use App\Enums\ShareDecision;
use App\Enums\ValidatedVia;
use App\Exceptions\Domain\ForbiddenTransition;
use App\Models\Story;
use App\States\Story\Recorded;
use App\States\Story\ToReview;
use App\States\Story\Transcribed;
use App\States\Story\Validated;
use Spatie\ModelStates\Transition;

/**
 * → VALIDÉE. Le seul chemin vers la validation, quel que soit l'état source.
 *
 * Une seule classe, comme le prévoit la règle de décision par défaut du bloc :
 * le paquet n'accepte qu'une transition par paire d'états, et deux gardes
 * différentes cohabitent sur `Transcribed → Validated`. La garde vit donc ici,
 * et refuse par `ForbiddenTransition` plutôt que de contourner par une
 * écriture directe de `state`.
 *
 * Trois chemins, tous explicites :
 *  - depuis TRANSCRITE : variante A, la décision de partage a été prise en fin
 *    d'enregistrement ; sans cette décision, la validation est refusée ;
 *  - depuis À RELIRE : variante B, le narrateur a relu puis validé ;
 *  - depuis ENREGISTRÉE : uniquement l'opérateur téléphone (D-9), qui doit
 *    exhiber un consentement `phone_call_recording` recueilli par téléphone et
 *    attribué à un opérateur nommé.
 */
final class ValidateStory extends Transition
{
    public function __construct(
        private readonly Story $story,
        private readonly ?ValidatedVia $via = null,
    ) {}

    public function handle(): Story
    {
        $via = $this->via ?? $this->inferVia();

        $this->guard($via);

        $this->story->state = new Validated($this->story);
        $this->story->validated_at = now();
        $this->story->validated_via = $via;
        $this->story->save();

        return $this->story;
    }

    /**
     * Le chemin de validation se déduit de l'état source, sauf pour
     * l'opérateur téléphone : celui-là doit être demandé explicitement.
     */
    private function inferVia(): ?ValidatedVia
    {
        return match (true) {
            $this->story->state instanceof Transcribed => ValidatedVia::RecordingEnd,
            $this->story->state instanceof ToReview => ValidatedVia::PostTranscription,
            default => null,
        };
    }

    private function guard(?ValidatedVia $via): void
    {
        $from = $this->story->state->getValue();

        if ($via === null) {
            throw ForbiddenTransition::guardFailed(
                $from,
                'validated',
                'the validation path must be given explicitly from this state',
            );
        }

        match (true) {
            $this->story->state instanceof Transcribed => $this->guardImmediate($from, $via),
            $this->story->state instanceof ToReview => $this->guardDeferred($from, $via),
            $this->story->state instanceof Recorded => $this->guardPhoneOperator($from, $via),
            default => throw ForbiddenTransition::notAllowed($from, 'validated'),
        };
    }

    private function guardImmediate(string $from, ValidatedVia $via): void
    {
        if (! in_array($via, [ValidatedVia::RecordingEnd, ValidatedVia::Mandate], true)) {
            throw ForbiddenTransition::guardFailed($from, 'validated', "validated_via [{$via->value}] is not available from this state");
        }

        if ($this->story->share_decision !== ShareDecision::Share) {
            throw ForbiddenTransition::guardFailed($from, 'validated', 'the narrator has not decided to share yet');
        }
    }

    private function guardDeferred(string $from, ValidatedVia $via): void
    {
        if (! in_array($via, [ValidatedVia::PostTranscription, ValidatedVia::Mandate], true)) {
            throw ForbiddenTransition::guardFailed($from, 'validated', "validated_via [{$via->value}] is not available from this state");
        }
    }

    private function guardPhoneOperator(string $from, ValidatedVia $via): void
    {
        if ($via !== ValidatedVia::PhoneOperator) {
            throw ForbiddenTransition::guardFailed($from, 'validated', 'only the phone operator validates a story that is merely recorded');
        }

        $narrator = $this->story->narrator;

        if ($narrator === null || ! $narrator->hasConsent(ConsentKind::PhoneCallRecording)) {
            throw ForbiddenTransition::guardFailed($from, 'validated', 'no oral agreement to the phone recording is on file');
        }
    }
}
