<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\Domain\ForbiddenTransition;
use App\Models\Story;
use App\States\Story\Trashed;
use Illuminate\Support\Facades\Log;

/**
 * Sort une histoire de la corbeille, dans la fenêtre de trente jours.
 *
 * Passé le délai, la transition refuse : la corbeille n'est pas un archivage
 * déguisé, et un produit qui promet trente jours doit tenir trente jours,
 * pas trente-et-un.
 */
final class RestoreStoryAction
{
    /**
     * La fenêtre est-elle encore ouverte ?
     *
     * La transition porte la garde et lève ; cette méthode existe pour que
     * l'écran n'offre pas un bouton qui échouera, et pour que le contrôleur
     * rende une phrase compréhensible plutôt qu'une erreur technique.
     */
    public static function isAvailableFor(Story $story): bool
    {
        if (! $story->state instanceof Trashed || $story->trashed_at === null) {
            return false;
        }

        $days = (int) config('product.stories.trash_retention_days');

        return $story->trashed_at->gte(now()->subDays($days));
    }

    public function handle(Story $story): Story
    {
        if (! $story->state instanceof Trashed) {
            return $story;
        }

        $target = $story->previousStateClass();

        if ($target === null) {
            throw ForbiddenTransition::guardFailed('trashed', 'unknown', 'no previous state was recorded');
        }

        $story->state->transitionTo($target);

        Log::info('story.restored', ['story_id' => $story->id, 'to' => $story->state->getValue()]);

        return $story;
    }
}
