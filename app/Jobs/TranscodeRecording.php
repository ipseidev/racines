<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Recording;
use App\Services\Storage\MediaStorage;
use App\Support\ObjectKeys;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use RuntimeException;

/**
 * Mesure la durée de l'audio et en tire un MP3 lisible partout.
 *
 * L'original ne bouge pas — c'est le principe non négociable. Le dérivé sert
 * l'écoute famille (bloc 08) et le QR du livre : un `webm` ne se lit pas sur
 * un iPhone de 2018, un MP3 se lit partout, et l'écoute est ce que la famille
 * fera le plus souvent.
 *
 * Idempotent : rejoué, il ne refait rien s'il a déjà produit son dérivé.
 */
final class TranscodeRecording implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public readonly string $recordingId)
    {
        $this->onQueue('media');
    }

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [30, 120, 600];
    }

    public function handle(MediaStorage $storage): void
    {
        $recording = Recording::query()->find($this->recordingId);

        if ($recording === null || ! $recording->isConfirmed()) {
            return;
        }

        $source = $recording->original_path ?? self::firstSegmentKey($recording);

        if ($source === null) {
            return;
        }

        if ($recording->derived_mp3_path !== null && $recording->duration_seconds !== null) {
            SubmitTranscription::dispatch($recording->id);

            return;
        }

        $directory = storage_path('app/transcode/'.$recording->id);
        File::ensureDirectoryExists($directory);

        $extension = ObjectKeys::extensionForMime((string) $recording->original_mime);
        $inputPath = $directory.'/source.'.$extension;
        $outputPath = $directory.'/derived.mp3';

        try {
            File::put($inputPath, $storage->get($source));

            $recording->duration_seconds = self::probeDuration($inputPath);

            $result = Process::timeout(600)->run([
                (string) config('product.media.ffmpeg'),
                '-i', $inputPath,
                '-codec:a', 'libmp3lame',
                '-b:a', '128k',
                '-ac', '1',
                '-y', $outputPath,
            ]);

            if ($result->failed() || ! File::exists($outputPath)) {
                throw new RuntimeException('ffmpeg n’a pas produit de dérivé MP3 : '.$result->errorOutput());
            }

            $derivedKey = ObjectKeys::recordingDerivative($recording, 'mp3');
            $storage->put($derivedKey, File::get($outputPath), 'audio/mpeg');

            $recording->derived_mp3_path = $derivedKey;
            $recording->save();

            Log::info('recording.transcoded', [
                'recording_id' => $recording->id,
                'duration_seconds' => $recording->duration_seconds,
            ]);
        } finally {
            File::deleteDirectory($directory);
        }

        SubmitTranscription::dispatch($recording->id);
    }

    /**
     * Durée réelle, lue par `ffprobe`.
     *
     * Celle annoncée par le navigateur est indicative : un enregistrement
     * interrompu, un conteneur mal fermé, et elle mentait. C'est celle-ci qui
     * compte pour les critères book-ready (R-6) et le fair use.
     */
    private static function probeDuration(string $path): ?string
    {
        $result = Process::timeout(60)->run([
            (string) config('product.media.ffprobe'),
            '-v', 'error',
            '-show_entries', 'format=duration',
            '-of', 'default=noprint_wrappers=1:nokey=1',
            $path,
        ]);

        if ($result->failed()) {
            return null;
        }

        $duration = trim($result->output());

        return is_numeric($duration) ? number_format((float) $duration, 2, '.', '') : null;
    }

    private static function firstSegmentKey(Recording $recording): ?string
    {
        $key = $recording->segments[0]['key'] ?? null;

        return is_string($key) ? $key : null;
    }
}
