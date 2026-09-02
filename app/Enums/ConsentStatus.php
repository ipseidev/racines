<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasTranslatedLabel;

/**
 * Une révocation n'efface pas l'accord : elle ajoute une ligne (doc 04 §2).
 */
enum ConsentStatus: string
{
    use HasTranslatedLabel;

    case Granted = 'granted';
    case Revoked = 'revoked';
}
