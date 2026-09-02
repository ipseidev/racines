<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\LexiconEntry;

/**
 * Retire un nom propre du lexique. Les textes déjà corrigés ne changent pas :
 * on ne réécrit pas l'histoire pour un réglage.
 */
final class RemoveLexiconEntry
{
    public function handle(LexiconEntry $entry): void
    {
        $entry->delete();
    }
}
