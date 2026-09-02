<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ShareDecision;
use App\Enums\StoryVisibility;
use App\Enums\TokenIssuedReason;
use App\Enums\ValidatedVia;
use App\Features\ValidationVariant;
use App\Models\Story;
use App\Notifications\ReviewReadyNotification;
use App\States\Story\Shared;
use App\States\Story\ToReview;
use App\States\Story\Transcribed;
use Illuminate\Support\Facades\Log;

/**
 * Tire les conséquences de la décision du narrateur, une fois le texte prêt.
 *
 * Quatre chemins, et un principe qui les gouverne tous : **le silence n'est
 * jamais un accord** (doc 04 §1). Sans décision explicite de partage, aucune
 * histoire ne devient visible — on demande, on ne suppose pas.
 *
 * En variante B, la question n'a pas été posée à l'enregistrement : la
 * relecture est demandée quoi qu'il arrive. Une décision arrivée d'ailleurs
 * — un projet dont la variante a changé, un opérateur téléphone — ne
 * court-circuite pas cette relecture.
 */
final readonly class ApplyShareDecision
{
    public function __construct(
        private ValidateStoryAction $validate,
        private IssueRecordToken $tokens,
    ) {}

    public function handle(Story $story): void
    {
        if (! $story->state instanceof Transcribed) {
            // Déjà relue, déjà validée, déjà retirée : la décision a été
            // appliquée, ou n'a plus lieu de l'être. Rejouer est sans effet.
            return;
        }

        if (ValidationVariant::isDeferredFor($story->project)) {
            $this->askForReview($story, 'ready');

            return;
        }

        match ($story->share_decision) {
            ShareDecision::Share => $this->share($story),
            // Le narrateur a déjà répondu : on ne le relance pas.
            ShareDecision::KeepPrivate => null,
            ShareDecision::DecideLater => $this->askForReview($story, 'decide_later'),
            null => $this->askForReview($story, 'ready'),
        };
    }

    /**
     * Validation par le tap de fin d'enregistrement, puis partage.
     *
     * Une histoire réservée au livre est validée sans être partagée : le
     * narrateur a choisi le papier, pas la diffusion. `ShareStory` refuserait
     * la transition, et ce refus n'est pas une erreur à rattraper.
     */
    private function share(Story $story): void
    {
        $this->validate->handle($story, ValidatedVia::RecordingEnd);

        if ($story->visibility === StoryVisibility::BookOnly) {
            Log::info('story.validated_book_only', ['story_id' => $story->id]);

            return;
        }

        $story->state->transitionTo(Shared::class);
    }

    /**
     * @param  'ready'|'decide_later'  $reason
     */
    private function askForReview(Story $story, string $reason): void
    {
        $story->state->transitionTo(ToReview::class);

        // Un lien neuf : celui de l'enregistrement a pu être envoyé il y a
        // plusieurs jours, et le narrateur n'a pas à fouiller ses SMS pour
        // relire son propre récit.
        $issued = $this->tokens->handle($story, TokenIssuedReason::Rotation);

        $story->narrator->notify(new ReviewReadyNotification($story, $issued->plain, $reason));

        Log::info('story.review_requested', [
            'story_id' => $story->id,
            'reason' => $reason,
        ]);
    }
}
