<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ShareDecision;
use App\Enums\StoryVisibility;
use App\Enums\ValidatedVia;
use App\Models\Story;
use App\States\Story\Shared;
use App\States\Story\ToReview;
use App\States\Story\Transcribed;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Ce que devient une histoire après relecture.
 *
 * Trois issues, et une asymétrie assumée entre elles : **partager** et
 * **garder pour le livre** valident, parce que ce sont des décisions ; **garder
 * pour moi** ne valide pas, parce que ne rien diffuser n'est pas une décision
 * à graver. L'histoire redescend simplement à « transcrite », privée, et
 * reste modifiable depuis l'espace narrateur.
 *
 * C'est la règle §9 du bloc, appliquée à la lettre : en cas d'ambiguïté sur
 * « garder pour moi », l'histoire reste privée **et hors livre**. Le livre
 * n'inclut jamais une histoire qui n'a pas été explicitement validée.
 */
final readonly class ApplyReviewDecision
{
    public function __construct(
        private ValidateStoryAction $validate,
        private SetStoryVisibility $visibilities,
    ) {}

    /**
     * @param  list<string>  $familyMemberIds
     */
    public function handle(
        Story $story,
        ShareDecision $decision,
        bool $keepForBook = false,
        StoryVisibility $visibility = StoryVisibility::AllFamily,
        array $familyMemberIds = [],
    ): Story {
        return DB::transaction(fn (): Story => match ($decision) {
            ShareDecision::Share => $this->share($story, $visibility, $familyMemberIds),
            ShareDecision::KeepPrivate => $this->keepPrivate($story, $keepForBook),
            ShareDecision::DecideLater => $this->decideLater($story),
        });
    }

    /**
     * @param  list<string>  $familyMemberIds
     */
    private function share(Story $story, StoryVisibility $visibility, array $familyMemberIds): Story
    {
        // La visibilité est posée **avant** la transition : `ShareStory`
        // refuse une histoire réservée au livre, et ce refus doit porter sur
        // le choix que le narrateur vient de faire, pas sur l'ancien.
        if ($visibility !== StoryVisibility::BookOnly) {
            $this->visibilities->handle($story, $visibility, $familyMemberIds);
        }

        $this->validate->handle($story, ValidatedVia::PostTranscription, $story->narrator);

        if ($visibility === StoryVisibility::BookOnly) {
            $this->visibilities->handle($story, StoryVisibility::BookOnly);

            return $story;
        }

        $story->state->transitionTo(Shared::class);

        return $story;
    }

    private function keepPrivate(Story $story, bool $keepForBook): Story
    {
        if ($keepForBook) {
            // Un choix explicite : le papier oui, la diffusion non.
            $this->visibilities->handle($story, StoryVisibility::BookOnly);
            $this->validate->handle($story, ValidatedVia::PostTranscription, $story->narrator);

            return $story;
        }

        if ($story->state instanceof ToReview) {
            // On redescend à « transcrite » : privée, non validée, et le
            // narrateur pourra revenir dessus depuis son espace. La
            // transition écrit aussi la décision, pour que la chaîne de
            // transcription rejouée ne redemande pas une relecture faite.
            $story->state->transitionTo(Transcribed::class);
        }

        Log::info('story.kept_private', ['story_id' => $story->id]);

        return $story;
    }

    private function decideLater(Story $story): Story
    {
        if ($story->state instanceof Transcribed) {
            $story->state->transitionTo(ToReview::class);
        }

        // Rien d'autre : la règle `recorded_not_validated` du bloc 09 relance
        // deux fois au maximum, puis se taît.
        Log::info('story.review_postponed', ['story_id' => $story->id]);

        return $story;
    }
}
