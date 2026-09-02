<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\StoreVerbatimTranscript;
use App\Models\Recording;
use App\Models\TranscriptionJob;
use App\Notifications\TranscriptionFailedNotification;
use App\Services\Storage\MediaStorage;
use App\Services\Transcription\TranscriptionProvider;
use App\Services\Transcription\TranscriptionRequest;
use App\Support\AsrCallback;
use App\Support\Brand;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

/**
 * Envoie l'audio au fournisseur de transcription.
 *
 * L'URL donnée au fournisseur est présignée pour une heure : il n'a pas
 * besoin de plus, et un lien de longue durée sur l'audio d'une famille n'a
 * aucune raison d'exister.
 *
 * Le lexique du projet part **avec** la demande : donné d'avance,
 * « Kerhostin » sort « Kerhostin » ; donné après, il faut le corriger.
 */
final class SubmitTranscription implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public readonly string $recordingId)
    {
        $this->onQueue('transcription');
    }

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function handle(
        TranscriptionProvider $provider,
        MediaStorage $storage,
        StoreVerbatimTranscript $store,
    ): void {
        $recording = Recording::query()->find($this->recordingId);

        if ($recording === null || ! $recording->isConfirmed()) {
            return;
        }

        $job = TranscriptionJob::query()->firstOrCreate(
            ['recording_id' => $recording->id, 'provider' => $provider->name()],
        );

        if ($job->status->value === 'done') {
            return;
        }

        $key = $recording->derived_mp3_path ?? $recording->original_path ?? self::firstSegmentKey($recording);

        if ($key === null) {
            $job->markFailed('aucun objet à transcrire');

            return;
        }

        $request = new TranscriptionRequest(
            audioUrl: $storage->temporaryUrl($key, 60),
            language: 'fr',
            vocabulary: self::vocabularyFor($recording),
            callbackUrl: AsrCallback::urlFor($provider->name(), $recording->id),
        );

        $submitted = $provider->submit($recording, $request);

        $job->markProcessing($submitted->providerJobId);

        Log::info('transcription.submitted', [
            'recording_id' => $recording->id,
            'provider' => $provider->name(),
            'mode' => $submitted->mode,
            'vocabulary' => count($request->vocabulary),
        ]);

        if ($submitted->isImmediate() && $submitted->result !== null) {
            $store->handle($recording, $submitted->result);
            $job->markDone();
        }
    }

    public function failed(?Throwable $exception): void
    {
        $recording = Recording::query()->find($this->recordingId);

        TranscriptionJob::query()
            ->where('recording_id', $this->recordingId)
            ->get()
            ->each(fn (TranscriptionJob $job) => $job->markFailed($exception?->getMessage() ?? 'échec inconnu'));

        Log::error('transcription.failed', [
            'recording_id' => $this->recordingId,
            'reason' => $exception?->getMessage(),
        ]);

        if ($recording !== null) {
            // Une histoire enregistrée mais jamais transcrite est un silence
            // inexpliqué pour la famille : le support doit le voir.
            Notification::route('mail', Brand::supportEmail())
                ->notify(new TranscriptionFailedNotification($recording));
        }
    }

    /**
     * @return list<string>
     */
    private static function vocabularyFor(Recording $recording): array
    {
        return array_values($recording->story->project->lexiconEntries
            ->map(fn ($entry): string => $entry->spelling())
            ->unique()
            ->all());
    }

    private static function firstSegmentKey(Recording $recording): ?string
    {
        $key = $recording->segments[0]['key'] ?? null;

        return is_string($key) ? $key : null;
    }
}
