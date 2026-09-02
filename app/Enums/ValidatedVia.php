<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasTranslatedLabel;

/**
 * Chemin par lequel la validation explicite a été obtenue (R-4).
 *
 * Aucune valeur ne décrit un délai écoulé : la validation tacite n'existe pas.
 */
enum ValidatedVia: string
{
    use HasTranslatedLabel;

    case RecordingEnd = 'recording_end';
    case PostTranscription = 'post_transcription';
    case Mandate = 'mandate';
    case PhoneOperator = 'phone_operator';
}
