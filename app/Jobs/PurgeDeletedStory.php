<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Story;
use App\Services\Storage\MediaStorage;
use App\States\Story\Deleted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Efface le contenu d'une histoire supprimée : objets, textes, titre.
 *
 * Ce job est le seul endroit du produit qui supprime des objets du stockage.
 * Il ne s'exécute que sur une histoire déjà `deleted` — l'état est la
 * condition, pas un paramètre : un job rejoué sur une histoire restaurée ne
 * doit pas la vider.
 *
 * Ce qui reste après lui : la ligne `stories`, son `deleted_at`, sa question,
 * sa place dans la séquence. Ce qui part : l'audio original, le dérivé, la
 * réplique, les segments, les transcriptions, le titre et la réponse écrite.
 * Assez pour savoir qu'une histoire a existé, rien pour la reconstituer.
 */
final class PurgeDeletedStory implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public readonly string $storyId)
    {
        $this->onQueue('media');
    }

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function handle(MediaStorage $storage): void
    {
        $story = Story::query()->with('recordings')->find($this->storyId);

        if ($story === null || ! $story->state instanceof Deleted) {
            return;
        }

        $keys = [];

        foreach ($story->recordings as $recording) {
            foreach ([$recording->original_path, $recording->derived_mp3_path, $recording->replica_path] as $key) {
                if (is_string($key) && $key !== '') {
                    $keys[] = $key;
                }
            }

            foreach ($recording->segments ?? [] as $segment) {
                $key = $segment['key'] ?? null;

                if (is_string($key) && $key !== '') {
                    $keys[] = $key;
                }
            }
        }

        foreach (array_unique($keys) as $key) {
            $storage->delete($key);
        }

        DB::transaction(function () use ($story): void {
            // Le verbatim se supprime enfin : le déclencheur Postgres ne
            // s'y oppose plus, l'histoire est `deleted` (bloc 06).
            $story->transcripts()->delete();

            $story->recordings()->update([
                'original_path' => null,
                'derived_mp3_path' => null,
                'replica_path' => null,
                'segments' => null,
                'checksum_sha256' => null,
            ]);

            $story->forceFill([
                'title' => null,
                'written_answer' => null,
                'deleted_at' => $story->deleted_at ?? now(),
            ])->save();
        });

        Log::warning('story.purged', [
            'story_id' => $story->id,
            'objects_deleted' => count(array_unique($keys)),
        ]);
    }
}
