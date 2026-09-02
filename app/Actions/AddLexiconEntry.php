<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\LexiconEntry;
use App\Models\Project;
use Illuminate\Database\Eloquent\Model;

/**
 * Ajoute un nom propre au lexique du projet.
 *
 * Deux usages, et le second est le plus important : le terme est donné au
 * fournisseur ASR **avant** la transcription suivante, ce qui vaut mieux que
 * de corriger après.
 */
final class AddLexiconEntry
{
    public function handle(Project $project, string $term, ?string $replacement, Model $by): LexiconEntry
    {
        $entry = $project->lexiconEntries()->firstOrNew(['term' => trim($term)]);

        $entry->replacement = $replacement === null ? null : trim($replacement);
        $entry->created_by_type = $entry->exists ? $entry->created_by_type : $by->getMorphClass();
        $entry->created_by_id = $entry->exists ? $entry->created_by_id : (string) $by->getKey();
        $entry->save();

        return $entry;
    }
}
