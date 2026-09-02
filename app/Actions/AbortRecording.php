<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UploadStatus;
use App\Models\Recording;
use App\Services\Storage\MediaStorage;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Abandonne un envoi en cours et libère les parts déjà déposées.
 *
 * Le narrateur n'abandonne pas son histoire : il abandonne un envoi. Rien
 * n'est supprimé côté histoire, et le brouillon reste sur son téléphone.
 */
final readonly class AbortRecording
{
    public function __construct(private MediaStorage $storage) {}

    public function handle(Recording $recording): void
    {
        if ($recording->isConfirmed()) {
            // Un envoi confirmé ne s'abandonne pas : l'objet est en place.
            return;
        }

        foreach ($recording->segments ?? [] as $segment) {
            $key = $segment['key'] ?? null;
            $uploadId = $segment['upload_id'] ?? null;

            if (! is_string($key) || ! is_string($uploadId)) {
                continue;
            }

            try {
                $this->storage->abortMultipart($key, $uploadId);
            } catch (Throwable $exception) {
                Log::info('recording.abort_failed', [
                    'recording_id' => $recording->id,
                    'segment' => $segment['number'] ?? null,
                    'reason' => $exception->getMessage(),
                ]);
            }
        }

        $recording->upload_status = UploadStatus::Aborted;
        $recording->save();
    }
}
