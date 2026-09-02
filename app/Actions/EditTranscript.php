<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\TranscriptKind;
use App\Models\Transcript;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Enregistre une correction du texte.
 *
 * Une correction ne modifie rien : elle ajoute une version. L'historique reste
 * complet, et le verbatim reste le verbatim — il garde son statut de texte
 * courant **parmi les verbatims**, quoi qu'on corrige par-dessus.
 */
final class EditTranscript
{
    public function handle(Transcript $base, string $text, Model $editor): Transcript
    {
        return DB::transaction(function () use ($base, $text, $editor): Transcript {
            $previous = $base->story->transcripts()
                ->ofKind(TranscriptKind::Edited)
                ->current()
                ->first();

            // La version suit celle de la dernière correction si elle
            // existe, sinon celle du texte dont on part.
            $version = $base->version + 1;

            if ($previous instanceof Transcript) {
                $previous->is_current = false;
                $previous->save();
                $version = $previous->version + 1;
            }

            $edited = new Transcript([
                'kind' => TranscriptKind::Edited,
                'version' => $version,
                'provider' => 'human',
                'language' => $base->language,
                'text' => trim($text),
                'metadata' => [
                    'edited_from' => $base->id,
                    'edited_from_kind' => $base->kind->value,
                ],
            ]);

            $edited->story()->associate($base->story);
            $edited->recording()->associate($base->recording);
            $edited->source()->associate($base);
            $edited->edited_by_type = $editor->getMorphClass();
            $edited->edited_by_id = (string) $editor->getKey();
            $edited->save();

            return $edited;
        });
    }
}
