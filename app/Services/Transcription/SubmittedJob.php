<?php

declare(strict_types=1);

namespace App\Services\Transcription;

/**
 * Réponse d'un fournisseur à une soumission.
 *
 * Trois modes, parce que les fournisseurs ne se ressemblent pas : Gladia
 * rappelle par webhook, Deepgram répond tout de suite, et il faut pouvoir
 * interroger quand le rappel n'arrive pas. L'interface les accepte tous les
 * trois plutôt que d'imposer un mode et de tricher pour les autres.
 */
final readonly class SubmittedJob
{
    public const MODE_WEBHOOK = 'webhook';

    public const MODE_POLL = 'poll';

    public const MODE_SYNC = 'sync';

    public function __construct(
        public ?string $providerJobId,
        public string $mode,
        public ?TranscriptionResult $result = null,
    ) {}

    public function isImmediate(): bool
    {
        return $this->mode === self::MODE_SYNC && $this->result instanceof TranscriptionResult;
    }
}
