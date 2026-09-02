<?php

declare(strict_types=1);

namespace App\Services\Transcription;

/**
 * Ce qu'on demande à un fournisseur de transcription.
 *
 * Le vocabulaire vient du lexique du projet : « Kerhostin » donné d'avance
 * sort « Kerhostin », donné après il sort « Ker Austin » et il faut corriger.
 */
final readonly class TranscriptionRequest
{
    /**
     * @param  list<string>  $vocabulary
     */
    public function __construct(
        public string $audioUrl,
        public string $language = 'fr',
        public array $vocabulary = [],
        public ?string $callbackUrl = null,
    ) {}
}
