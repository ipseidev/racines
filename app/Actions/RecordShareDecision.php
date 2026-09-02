<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ShareDecision;
use App\Models\Story;
use App\States\Story\StoryState;
use Illuminate\Support\Facades\Log;

/**
 * Note ce que le narrateur veut faire de son histoire — et rien de plus.
 *
 * Aucune transition ici, volontairement. La décision est prise en fin
 * d'enregistrement, quand il n'y a encore aucun texte : l'appliquer tout de
 * suite validerait un récit que personne n'a lu. C'est
 * `ApplyShareDecision`, après la transcription, qui en tire les conséquences.
 *
 * Le narrateur peut changer d'avis jusque-là : un choix qui ne se reprend pas
 * n'est pas un choix, c'est un piège.
 */
final class RecordShareDecision
{
    public function handle(Story $story, ShareDecision $decision): Story
    {
        $story->share_decision = $decision;
        $story->share_decided_at = now();
        $story->save();

        Log::info('story.share_decision', [
            'story_id' => $story->id,
            'decision' => $decision->value,
            'state' => $story->state->getValue(),
        ]);

        return $story;
    }

    /**
     * Une décision n'a de sens que sur une histoire qui existe : proposée,
     * elle n'a pas encore de contenu, et la décision resterait orpheline.
     */
    public static function isAvailableFor(Story $story): bool
    {
        foreach (StoryState::RECORDED_OR_LATER as $state) {
            if ($story->state instanceof $state) {
                return true;
            }
        }

        return false;
    }
}
