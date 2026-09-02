<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhooks;

use App\Actions\StoreVerbatimTranscript;
use App\Models\Recording;
use App\Models\TranscriptionJob;
use App\Services\Transcription\TranscriptionProvider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Rappel d'un fournisseur de transcription.
 *
 * La signature est vérifiée par l'adaptateur, avant toute lecture du corps :
 * c'est lui qui sait comment son fournisseur signe. Un corps qui n'est pas
 * encore un résultat — statut intermédiaire — reçoit 202 sans rien changer.
 */
final readonly class AsrWebhookController
{
    public function __construct(
        private TranscriptionProvider $provider,
        private StoreVerbatimTranscript $store,
    ) {}

    public function __invoke(Request $request, string $provider, string $recording): JsonResponse
    {
        $model = Recording::query()->find($recording);

        // Enregistrement inconnu : on ne le dit pas, et on ne réessaie pas.
        if ($model === null) {
            Log::info('webhook.asr.unknown_recording', ['provider' => $provider]);

            return response()->json(status: 202);
        }

        $result = $this->provider->parseWebhook($request);

        if ($result === null) {
            return response()->json(status: 202);
        }

        $this->store->handle($model, $result);

        TranscriptionJob::query()
            ->where('recording_id', $model->id)
            ->where('provider', $this->provider->name())
            ->get()
            ->each(fn (TranscriptionJob $job) => $job->markDone());

        Log::info('webhook.asr.stored', [
            'recording_id' => $model->id,
            'provider' => $this->provider->name(),
        ]);

        return response()->json(status: 200);
    }
}
