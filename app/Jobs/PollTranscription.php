<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\StoreVerbatimTranscript;
use App\Enums\TranscriptionStatus;
use App\Models\TranscriptionJob;
use App\Services\Transcription\TranscriptionProvider;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Interroge les transcriptions en cours dont le rappel n'est pas arrivé.
 *
 * Le rappel est le chemin normal ; celui-ci est le filet. Un webhook perdu
 * — réseau, redéploiement, fournisseur qui n'a pas réessayé — laisserait une
 * histoire enregistrée sans texte, et personne ne le saurait.
 *
 * Au-delà d'une heure, on abandonne et on le dit : mieux vaut un échec visible
 * qu'un travail qui traîne indéfiniment dans la file.
 */
final class PollTranscription implements ShouldQueue
{
    use Queueable;

    /** Laisse au rappel le temps d'arriver avant d'interroger. */
    private const GRACE_SECONDS = 30;

    private const GIVE_UP_MINUTES = 60;

    public function __construct()
    {
        $this->onQueue('transcription');
    }

    public function handle(TranscriptionProvider $provider, StoreVerbatimTranscript $store): void
    {
        $pending = TranscriptionJob::query()
            ->where('status', TranscriptionStatus::Processing->value)
            ->whereNotNull('provider_job_id')
            ->where('submitted_at', '<=', now()->subSeconds(self::GRACE_SECONDS))
            ->get();

        foreach ($pending as $job) {
            if ($job->submitted_at !== null && $job->submitted_at->lt(now()->subMinutes(self::GIVE_UP_MINUTES))) {
                $job->markFailed('abandonné après '.self::GIVE_UP_MINUTES.' minutes sans résultat');

                continue;
            }

            try {
                $result = $provider->fetch((string) $job->provider_job_id);
            } catch (Throwable $exception) {
                $job->markFailed($exception->getMessage());

                continue;
            }

            if ($result === null) {
                continue;
            }

            $store->handle($job->recording, $result);
            $job->markDone();

            Log::info('transcription.polled', [
                'recording_id' => $job->recording_id,
                'provider' => $job->provider,
            ]);
        }
    }
}
