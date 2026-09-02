<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasTranslatedLabel;

/**
 * Forme de la réponse du narrateur (P0-5).
 */
enum AnswerType: string
{
    use HasTranslatedLabel;

    case Audio = 'audio';
    case Text = 'text';
    case Phone = 'phone';
}
