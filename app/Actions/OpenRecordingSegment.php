<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UploadStatus;
use App\Models\Recording;
use App\Services\Storage\MediaStorage;
use App\Support\ObjectKeys;
use DomainException;

/**
 * Ouvre un segment d'enregistrement.
 *
 * Un segment par continuité de flux : le premier à l'ouverture, un nouveau
 * chaque fois qu'un appel entrant, une veille ou une purge d'onglet coupe
 * `MediaRecorder`. Chacun a son propre envoi multipart, donc ses propres parts
 * reprenables — c'est ce qui fait qu'une interruption coûte une couture, pas
 * l'histoire.
 */
final readonly class OpenRecordingSegment
{
    /** Au-delà, ce n'est plus une interruption mais une boucle. */
    private const MAX_SEGMENTS = 200;

    public function __construct(private MediaStorage $storage) {}

    /**
     * @return array{number: int, upload_id: string, key: string}
     */
    public function handle(Recording $recording): array
    {
        if ($recording->isConfirmed()) {
            throw new DomainException("Recording [{$recording->id}] is already confirmed.");
        }

        $segments = $recording->segments ?? [];

        if (count($segments) >= self::MAX_SEGMENTS) {
            throw new DomainException("Recording [{$recording->id}] has too many segments.");
        }

        $number = count($segments) + 1;
        $extension = ObjectKeys::extensionForMime((string) $recording->original_mime);
        $key = ObjectKeys::recordingSegment($recording, $number, $extension);
        $uploadId = $this->storage->createMultipartUpload($key, (string) $recording->original_mime);

        $segments[] = [
            'number' => $number,
            'upload_id' => $uploadId,
            'key' => $key,
            'bytes' => null,
        ];

        $recording->segments = $segments;
        $recording->upload_status = UploadStatus::Uploading;
        $recording->save();

        return ['number' => $number, 'upload_id' => $uploadId, 'key' => $key];
    }
}
