<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ValidatedVia;
use App\Events\StoryValidated;
use App\Models\Story;
use App\States\Story\Validated;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * Le seul chemin applicatif vers la validation d'une histoire.
 *
 * Tout passe par ici : la transition, le chemin de validation, l'acteur,
 * l'événement. L'horodatage et `validated_via` sont posés par la transition
 * elle-même, qui porte aussi les gardes ; la révocation du lien
 * d'enregistrement est faite par `RevokeRecordTokensOnValidation`, qui écoute
 * le changement d'état — de cette façon, aucun chemin de validation ne peut
 * l'oublier.
 *
 * C'est cette concentration qui rend le critère de sortie du bloc 07
 * vérifiable : aucune histoire ne devient visible sans qu'un `validated_at`
 * et un `validated_via` aient été posés par ce chemin.
 */
final class ValidateStoryAction
{
    public function handle(Story $story, ValidatedVia $via, ?Model $actor = null): Story
    {
        if ($story->state instanceof Validated) {
            // Rejouer ne doit pas réécrire l'horodatage, dont dépend l'ordre
            // du fil famille.
            return $story;
        }

        $story->state->transitionTo(Validated::class, $via);

        Log::info('story.validated', [
            'story_id' => $story->id,
            'via' => $via->value,
            'actor_type' => $actor?->getMorphClass(),
            'actor_id' => $actor === null ? null : (string) $actor->getKey(),
        ]);

        StoryValidated::dispatch($story, $via, $actor);

        return $story;
    }
}
