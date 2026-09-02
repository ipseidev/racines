<?php

declare(strict_types=1);

namespace App\Services\Transcription;

use App\Models\Recording;
use Illuminate\Http\Request;

/**
 * Transcription, derrière une interface.
 *
 * Le dossier fait de la qualité ASR sur voix âgées une hypothèse à mesurer,
 * pas à supposer : il faut donc pouvoir changer de fournisseur sur la foi d'un
 * banc d'essai, sans toucher au pipeline. D'où cette interface, et le second
 * adaptateur livré d'emblée.
 */
interface TranscriptionProvider
{
    public function name(): string;

    public function submit(Recording $recording, TranscriptionRequest $request): SubmittedJob;

    /**
     * Résultat d'un travail en cours, ou `null` s'il n'est pas prêt.
     */
    public function fetch(string $providerJobId): ?TranscriptionResult;

    /**
     * Lit un rappel du fournisseur. Rend `null` si le corps n'est pas un
     * résultat exploitable ; lève si la signature ne tient pas.
     */
    public function parseWebhook(Request $request): ?TranscriptionResult;
}
