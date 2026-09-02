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
 * Recolle les segments d'un enregistrement interrompu.
 *
 * Un appel entrant, une mise en veille ou une purge d'onglet coupe le flux :
 * le navigateur repart sur un nouveau segment plutôt que de perdre ce qui
 * précède. Ce job en fait un fichier unique, par copie de flux (`-c copy`),
 * donc sans réencodage ni perte de qualité.
 *
 * Les segments d'origine ne sont pas supprimés : ils sont la trace de
 * l'interruption, et « l'audio source est sacré ».
 */
final class ConcatenateSegments implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public readonly string $recordingId)
    {
        $this->onQueue('media');
    }

    public function handle(MediaStorage $storage): void
    {
        $recording = Recording::query()->find($this->recordingId);

        if ($recording === null || $recording->segmentCount() < 2) {
            return;
        }

        $extension = ObjectKeys::extensionForMime((string) $recording->original_mime);
        $directory = storage_path('app/concat/'.$recording->id);

        File::ensureDirectoryExists($directory);

        try {
            $list = [];

            foreach ($recording->segments ?? [] as $segment) {
                $number = (int) ($segment['number'] ?? 0);
                $key = $segment['key'] ?? null;

                if ($number < 1 || ! is_string($key)) {
                    continue;
                }

                $path = $directory.'/segment-'.str_pad((string) $number, 2, '0', STR_PAD_LEFT).'.'.$extension;

                File::put($path, $storage->get($key));
                $list[] = "file '{$path}'";
            }

            if (count($list) < 2) {
                return;
            }

            $listPath = $directory.'/list.txt';
            $outputPath = $directory.'/original.'.$extension;

            File::put($listPath, implode(PHP_EOL, $list));

            $result = Process::run([
                (string) config('product.media.ffmpeg', '/usr/bin/ffmpeg'),
                '-f', 'concat', '-safe', '0',
                '-i', $listPath,
                '-c', 'copy',
                '-y', $outputPath,
            ]);

            if ($result->failed()) {
                throw new RuntimeException('ffmpeg concat failed: '.$result->errorOutput());
            }

            // Un ffmpeg qui rend 0 sans produire de fichier existe : mieux
            // vaut un job réessayé qu'un `original_path` qui pointe le vide.
            if (! File::exists($outputPath)) {
                throw new RuntimeException("ffmpeg reported success but wrote no file for [{$recording->id}].");
            }

            $originalKey = ObjectKeys::recordingOriginal($recording, $extension);
            $storage->put($originalKey, File::get($outputPath), (string) $recording->original_mime);

            $recording->original_path = $originalKey;
            $recording->original_bytes = $storage->head($originalKey)->bytes;
            $recording->save();

            Log::info('recording.segments_concatenated', [
                'recording_id' => $recording->id,
                'segments' => count($list),
            ]);
        } finally {
            File::deleteDirectory($directory);
        }
    }
}
