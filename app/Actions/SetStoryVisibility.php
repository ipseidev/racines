<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\StoryVisibility;
use App\Models\Story;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Choisit qui peut écouter une histoire.
 *
 * Trois réglages, et un effet **immédiat** : un narrateur qui retire un accès
 * doit l'avoir retiré, pas l'avoir programmé. Aucune notion de délai, aucun
 * cache à invalider ailleurs.
 *
 * La liste blanche est nettoyée à chaque changement : rouvrir à tous puis
 * restreindre à nouveau ne doit pas ressusciter d'anciens invités.
 */
final class SetStoryVisibility
{
    /**
     * @param  list<string>  $familyMemberIds
     */
    public function handle(Story $story, StoryVisibility $visibility, array $familyMemberIds = []): Story
    {
        if ($visibility === StoryVisibility::Restricted && $familyMemberIds === []) {
            // « Restreint à personne » est ambigu : c'est « garder pour moi »,
            // et ça se dit autrement. On refuse plutôt que de deviner.
            throw new InvalidArgumentException(
                'Une visibilité restreinte demande au moins un proche désigné.',
            );
        }

        // Les identifiants viennent d'un formulaire : seuls les proches de
        // *ce* projet entrent dans la liste. Une liste blanche qui accepte
        // n'importe quel identifiant n'est pas une liste blanche.
        $allowed = $visibility === StoryVisibility::Restricted
            ? $story->project->familyMembers()->whereKey($familyMemberIds)->pluck('id')->all()
            : [];

        DB::transaction(function () use ($story, $visibility, $allowed): void {
            $story->visibility = $visibility;
            $story->save();

            $story->allowedFamilyMembers()->sync($allowed);
        });

        Log::info('story.visibility_changed', [
            'story_id' => $story->id,
            'visibility' => $visibility->value,
            'allowed_count' => count($allowed),
        ]);

        return $story;
    }
}
