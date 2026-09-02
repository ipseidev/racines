<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasTranslatedLabel;

/**
 * Décision de partage prise par le narrateur (R-4).
 */
enum ShareDecision: string
{
    use HasTranslatedLabel;

    case Share = 'share';
    case KeepPrivate = 'keep_private';
    case DecideLater = 'decide_later';
}
