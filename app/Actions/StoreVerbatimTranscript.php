<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\TranscriptKind;
use App\Jobs\RenderFluide;
use App\Models\Project;
use App\Models\Recording;
use App\Models\Transcript;
use App\Services\Transcription\TranscriptionResult;
use App\States\Story\Recorded;
use App\States\Story\Transcribed;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Enregistre le verbatim d'un enregistrement.
 *
 * Le lexique est appliqué **au texte** et non aux mots horodatés : les mots
 * servent à suivre l'audio, et changer leur contenu décalerait le suivi. Le
 * texte, lui, est ce qui va dans le livre — « Kerhostin » y doit s'écrire
 * comme la grand-mère l'écrit.
 */
final class StoreVerbatimTranscript
{
    public function handle(Recording $recording, TranscriptionResult $result): ?Transcript
    {
        if ($result->isEmpty()) {
            Log::warning('transcription.empty', ['recording_id' => $recording->id]);

            return null;
        }

        $story = $recording->story;

        return DB::transaction(function () use ($recording, $result, $story): Transcript {
            $existing = $story->transcripts()
                ->ofKind(TranscriptKind::Verbatim)
                ->current()
                ->first();

            // Un second verbatim courant n'existe pas : réenregistrer produit
            // une nouvelle version, l'ancienne reste consultable.
            $version = 1;

            if ($existing instanceof Transcript) {
                $existing->is_current = false;
                $existing->save();
                $version = $existing->version + 1;
            }

            $transcript = new Transcript([
                'kind' => TranscriptKind::Verbatim,
                'version' => $version,
                'provider' => (string) ($result->providerMetadata['provider'] ?? 'unknown'),
                'language' => $result->language,
                'text' => self::applyLexicon($result->text, $story->project),
                'words' => $result->words,
                'metadata' => $result->providerMetadata,
            ]);

            $transcript->story()->associate($story);
            $transcript->recording()->associate($recording);
            $transcript->save();

            if ($story->state instanceof Recorded) {
                $story->state->transitionTo(Transcribed::class);
            }

            if ($story->state instanceof Transcribed || $story->refresh()->state instanceof Transcribed) {
                RenderFluide::dispatch($transcript->id);
            }

            Log::info('transcription.stored', [
                'story_id' => $story->id,
                'recording_id' => $recording->id,
                'version' => $version,
                'characters' => mb_strlen($transcript->text),
            ]);

            return $transcript;
        });
    }

    /**
     * Corrige les noms propres du projet, sans toucher au reste.
     */
    public static function applyLexicon(string $text, Project $project): string
    {
        foreach ($project->lexiconEntries as $entry) {
            if ($entry->replacement === null || $entry->replacement === $entry->term) {
                continue;
            }

            $text = (string) preg_replace(
                '/\b'.preg_quote($entry->term, '/').'\b/iu',
                $entry->replacement,
                $text,
            );
        }

        return $text;
    }
}
