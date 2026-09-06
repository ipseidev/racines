<?php

declare(strict_types=1);

namespace App\Enums;

use App\States\Story\Deleted;
use App\States\Story\InBook;
use App\States\Story\Shared;
use App\States\Story\StoryState;
use App\States\Story\Trashed;
use App\States\Story\Validated;

/**
 * Ce qu'un export laisse voir, selon qui le demande.
 *
 * La distinction n'est pas technique : **l'Initiateur·rice ne reçoit que ce
 * que le narrateur a validé.** Un export qui livrerait les brouillons, les
 * récits masqués ou ceux dont il n'a pas décidé du partage contournerait
 * toute la souveraineté du bloc 07 — par un bouton « télécharger mes
 * données », c'est-à-dire par la porte de derrière.
 *
 * Le narrateur, lui, reçoit tout ce qui existe encore. La corbeille exceptée :
 * il l'a jetée, et la lui rendre dans un ZIP serait la vider de son sens.
 */
enum ExportScope: string
{
    case Initiator = 'initiator';
    case Narrator = 'narrator';

    /**
     * Les états d'histoire que cette portée laisse entrer.
     *
     * @return list<string>
     */
    public function states(): array
    {
        if ($this === self::Initiator) {
            return [Validated::$name, Shared::$name, InBook::$name];
        }

        /*
         * Tout sauf la corbeille et la suppression : masquée ou archivée, une
         * histoire reste la sienne. Ce qu'il a jeté, en revanche, ne lui est
         * pas rendu dans un ZIP — cela viderait le geste de son sens.
         *
         * `keys()` : `StoryState::all()` rend une **collection** indexée par
         * le nom de l'état, la classe en valeur.
         */
        $exclus = [Trashed::$name, Deleted::$name];
        $etats = [];

        foreach (array_keys(collect(StoryState::all())->all()) as $state) {
            $state = (string) $state;

            if (! in_array($state, $exclus, true)) {
                $etats[] = $state;
            }
        }

        return $etats;
    }
}
