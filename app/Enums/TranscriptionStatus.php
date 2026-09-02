<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasTranslatedLabel;

/**
 * Étape d'une demande de transcription.
 */
enum TranscriptionStatus: string
{
    use HasTranslatedLabel;

    case Queued = 'queued';
    case Processing = 'processing';
    case Done = 'done';
    case Failed = 'failed';
}
