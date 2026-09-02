<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Recording;
use App\Services\Storage\MediaStorage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Réplique l'audio confirmé vers un second bucket.
 *
 * Le doc 04 §11 exige zéro perte après « histoire enregistrée » : un seul
 * bucket, même très fiable, reste un seul bucket. La réplication est
 * idempotente et réessayée longtemps ; son échec définitif est un incident
 * P1, journalisé comme tel.
 */
final class ReplicateRecording implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public function __construct(public readonly string $recordingId)
    {
        $this->onQueue('media');
    }

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [30, 120, 600, 1800, 3600];
    }

    public function handle(MediaStorage $storage): void
    {
        $recording = Recording::query()->find($this->recordingId);

        if ($recording === null || ! $recording->isConfirmed()) {
            return;
        }

        if ($recording->replicated_at !== null) {
            return;
        }

        // On réplique la **source** : les segments. Le fichier recollé se
        // redérive d'eux, l'inverse est faux.
        $keys = [];

        foreach ($recording->segments ?? [] as $segment) {
            if (is_string($segment['key'] ?? null)) {
                $keys[] = $segment['key'];
            }
        }

        if ($recording->original_path !== null) {
            $keys[] = $recording->original_path;
        }

        $keys = array_values(array_unique($keys));

        if ($keys === []) {
            return;
        }

        foreach ($keys as $key) {
            $storage->copy($key, 'r2_replica');
        }

        $recording->replica_path = $recording->original_path ?? $keys[0];
        $recording->replicated_at = now();
        $recording->save();

        Log::info('recording.replicated', [
            'recording_id' => $recording->id,
            'story_id' => $recording->story_id,
            'objects' => count($keys),
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('recording.replication_failed', [
            'recording_id' => $this->recordingId,
            'reason' => $exception?->getMessage(),
        ]);
    }
}
