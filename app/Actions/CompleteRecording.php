<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AnswerType;
use App\Enums\UploadStatus;
use App\Jobs\ConcatenateSegments;
use App\Jobs\ReplicateRecording;
use App\Jobs\TranscodeRecording;
use App\Models\Recording;
use App\Services\Storage\MediaStorage;
use App\States\Story\Recorded;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Conclut un envoi et n'annonce l'enregistrement qu'une fois l'objet vérifié.
 *
 * C'est le point le plus sensible du produit. Le doc 04 §11 exige zéro perte
 * après « histoire enregistrée » : la confirmation à l'écran ne vient donc pas
 * de la fin de l'envoi côté navigateur, mais d'un `HeadObject` par segment qui
 * dit que le stockage détient l'objet et de quelle taille. Si un seul segment
 * manque, l'envoi est marqué en échec, l'histoire ne bouge pas, et le
 * narrateur voit « Réessayer » — jamais un remerciement.
 *
 * Un enregistrement d'un seul segment porte directement son `original_path`.
 * Un enregistrement interrompu est confirmé sur ses segments, qui sont ce qui
 * est en sécurité, et son fichier recollé arrive ensuite.
 */
final readonly class CompleteRecording
{
    public function __construct(private MediaStorage $storage) {}

    /**
     * @param  list<array{number: int, parts: list<array{number: int, etag: string}>}>  $segments
     */
    public function handle(
        Recording $recording,
        array $segments,
        ?float $clientDurationSeconds = null,
    ): bool {
        $declared = collect($recording->segments ?? [])->keyBy('number');
        $total = 0;
        $confirmedSegments = [];

        foreach ($segments as $segment) {
            $known = $declared->get($segment['number']);

            if (! is_array($known) || ! is_string($known['upload_id'] ?? null) || ! is_string($known['key'] ?? null)) {
                $this->markFailed($recording, "unknown segment [{$segment['number']}]");

                return false;
            }

            try {
                $this->storage->completeMultipart($known['key'], $known['upload_id'], $segment['parts']);
                $object = $this->storage->head($known['key']);
            } catch (Throwable $exception) {
                $this->markFailed($recording, $exception->getMessage());

                return false;
            }

            if ($object->bytes <= 0) {
                $this->markFailed($recording, "segment [{$segment['number']}] is empty");

                return false;
            }

            $total += $object->bytes;
            $confirmedSegments[] = [
                'number' => $segment['number'],
                'upload_id' => $known['upload_id'],
                'key' => $known['key'],
                'bytes' => $object->bytes,
                'etag' => $object->etag,
            ];
        }

        if ($confirmedSegments === []) {
            $this->markFailed($recording, 'no segment submitted');

            return false;
        }

        $max = (int) config('product.recording.max_bytes');

        if ($total > $max) {
            $this->markFailed($recording, "total size [{$total}] exceeds the limit [{$max}]");

            return false;
        }

        usort($confirmedSegments, fn (array $a, array $b): int => $a['number'] <=> $b['number']);

        $recording->segments = $confirmedSegments;
        $recording->original_bytes = $total;
        $recording->upload_status = UploadStatus::Completed;
        $recording->confirmed_at = now();

        if (count($confirmedSegments) === 1) {
            $recording->original_path = $confirmedSegments[0]['key'];
        }

        if ($clientDurationSeconds !== null) {
            // Durée annoncée par le navigateur, indicative : `ffprobe` donnera
            // la vraie au bloc 06. On la garde pour détecter les écarts.
            $recording->device_info = [
                ...($recording->device_info ?? []),
                'client_duration_seconds' => $clientDurationSeconds,
            ];
        }

        $recording->save();

        $story = $recording->story;

        // Une histoire déjà enregistrée reste enregistrée : le narrateur qui
        // recommence ne provoque pas une transition impossible.
        if (! $story->state instanceof Recorded) {
            $story->state->transitionTo(Recorded::class, AnswerType::Audio);
        }

        // Chaîne délibérée : on réplique la source **avant** d'en dériver
        // quoi que ce soit. Un dérivé sans source répliquée n'a pas de valeur.
        // La couture, quand il y en a une, précède le transcodage : c'est le
        // fichier recollé qui doit être transcrit et transcodé.
        Bus::chain(array_values(array_filter([
            new ReplicateRecording($recording->id),
            count($confirmedSegments) > 1 ? new ConcatenateSegments($recording->id) : null,
            new TranscodeRecording($recording->id),
        ])))->dispatch();

        Log::info('recording.confirmed', [
            'recording_id' => $recording->id,
            'story_id' => $story->id,
            'bytes' => $total,
            'segments' => count($confirmedSegments),
        ]);

        return true;
    }

    private function markFailed(Recording $recording, string $reason): void
    {
        $recording->upload_status = UploadStatus::Failed;
        $recording->save();

        Log::warning('recording.not_confirmed', [
            'recording_id' => $recording->id,
            'story_id' => $recording->story_id,
            'reason' => $reason,
        ]);
    }
}
