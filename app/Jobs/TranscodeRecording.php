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
 * Mesure la durée et tire de l'original ce qui se lit partout.
 *
 * L'original ne bouge pas — c'est le principe non négociable. Les dérivés
 * servent l'écoute famille (bloc 08) et le QR du livre : un `webm` ne se lit
 * pas sur un iPhone de 2018, un MP3 se lit partout, et l'écoute est ce que la
 * famille fera le plus souvent.
 *
 * D'un récit filmé (T-210), on tire **deux** dérivés :
 *
 *  - le même MP3, extrait de la piste sonore. C'est lui qui part à la
 *    transcription, qui sert le QR du livre et le proche qui préfère écouter
 *    dans le train. Toute la chaîne d'aval ignore donc qu'il y a eu une
 *    caméra, et c'est voulu.
 *  - un MP4 H.264/AAC, parce que Chrome Android rend du WebM qu'aucun iPhone
 *    ne sait lire. Sans lui, une grand-mère filmée sur Android serait
 *    invisible pour la moitié de sa famille. Quand l'original est déjà un
 *    MP4, on se contente de le remuxer : aucune perte, quelques secondes.
 *
 * Idempotent : rejoué, il ne refait que ce qui manque.
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

        $needsAudio = $recording->derived_mp3_path === null || $recording->duration_seconds === null;
        $needsVideo = $recording->isVideo() && $recording->derived_mp4_path === null;

        if (! $needsAudio && ! $needsVideo) {
            SubmitTranscription::dispatch($recording->id);

            return;
        }

        $directory = storage_path('app/transcode/'.$recording->id);
        File::ensureDirectoryExists($directory);

        $extension = ObjectKeys::extensionForMime((string) $recording->original_mime);
        $inputPath = $directory.'/source.'.$extension;

        try {
            // Une seule descente du fichier, même quand deux dérivés en
            // sortent : c'est la partie lente sur une vidéo de 300 Mo.
            File::put($inputPath, $storage->get($source));

            $recording->duration_seconds = self::probeDuration($inputPath);

            if ($needsAudio) {
                $recording->derived_mp3_path = $this->deriveMp3($recording, $storage, $inputPath, $directory);
            }

            if ($needsVideo) {
                $recording->derived_mp4_path = $this->deriveMp4($recording, $storage, $inputPath, $directory, $extension);
            }

            $recording->save();

            Log::info('recording.transcoded', [
                'recording_id' => $recording->id,
                'kind' => $recording->kind->value,
                'duration_seconds' => $recording->duration_seconds,
                'video_derived' => $needsVideo,
            ]);
        } finally {
            File::deleteDirectory($directory);
        }

        SubmitTranscription::dispatch($recording->id);
    }

    /**
     * Le MP3 : la voix, quel que soit le conteneur d'origine.
     *
     * `-vn` est là pour la vidéo : sans lui, ffmpeg tente de porter une image
     * dans le MP3 en pochette et échoue sur certains conteneurs.
     */
    private function deriveMp3(Recording $recording, MediaStorage $storage, string $inputPath, string $directory): string
    {
        $outputPath = $directory.'/derived.mp3';

        $result = Process::timeout(600)->run([
            (string) config('product.media.ffmpeg'),
            '-i', $inputPath,
            '-vn',
            '-codec:a', 'libmp3lame',
            '-b:a', '128k',
            '-ac', '1',
            '-y', $outputPath,
        ]);

        if ($result->failed() || ! File::exists($outputPath)) {
            throw new RuntimeException('ffmpeg n’a pas produit de dérivé MP3 : '.$result->errorOutput());
        }

        $key = ObjectKeys::recordingDerivative($recording, 'mp3');
        $storage->put($key, File::get($outputPath), 'audio/mpeg');

        return $key;
    }

    /**
     * Le MP4 lisible partout.
     *
     * Un original déjà en MP4 est seulement remuxé — `-c copy` — avec
     * l'index déplacé en tête (`+faststart`), sans quoi le lecteur d'un
     * proche télécharge tout le fichier avant d'afficher la première image.
     * Un WebM, lui, est réencodé en H.264/AAC, et redescendu à la hauteur de
     * la configuration s'il la dépasse : personne ne regarde un souvenir de
     * famille en 4K, et chaque mégaoctet est un mégaoctet de plus à héberger
     * pendant toute la durée d'engagement.
     */
    private function deriveMp4(
        Recording $recording,
        MediaStorage $storage,
        string $inputPath,
        string $directory,
        string $extension,
    ): string {
        $outputPath = $directory.'/derived.mp4';

        $command = [(string) config('product.media.ffmpeg'), '-i', $inputPath];

        if ($extension === 'mp4') {
            $command = [...$command, '-c', 'copy'];
        } else {
            $height = (int) config('product.recording.video.height', 720);
            $source = self::probeHeight($inputPath);

            $command = [
                ...$command,
                ...($source !== null && $source > $height ? ['-vf', "scale=-2:{$height}"] : []),
                '-c:v', 'libx264',
                '-preset', 'veryfast',
                '-crf', '26',
                '-pix_fmt', 'yuv420p',
                '-c:a', 'aac',
                '-b:a', '128k',
            ];
        }

        $result = Process::timeout(1800)->run([...$command, '-movflags', '+faststart', '-y', $outputPath]);

        if ($result->failed() || ! File::exists($outputPath)) {
            throw new RuntimeException('ffmpeg n’a pas produit de dérivé MP4 : '.$result->errorOutput());
        }

        $key = ObjectKeys::recordingDerivative($recording, 'mp4');
        $storage->put($key, File::get($outputPath), 'video/mp4');

        return $key;
    }

    /**
     * Hauteur de l'image, ou `null` si le fichier n'en a pas — un conteneur
     * vidéo dont la piste image manque reste transcodable en son.
     */
    private static function probeHeight(string $path): ?int
    {
        $result = Process::timeout(60)->run([
            (string) config('product.media.ffprobe'),
            '-v', 'error',
            '-select_streams', 'v:0',
            '-show_entries', 'stream=height',
            '-of', 'default=noprint_wrappers=1:nokey=1',
            $path,
        ]);

        if ($result->failed()) {
            return null;
        }

        $height = trim($result->output());

        return is_numeric($height) ? (int) $height : null;
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
