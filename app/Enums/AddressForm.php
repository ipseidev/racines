<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasTranslatedLabel;

/**
 * Vouvoiement ou tutoiement des pages narrateur (convention §10).
 */
enum AddressForm: string
{
    use HasTranslatedLabel;

    case Vous = 'vous';
    case Tu = 'tu';
}
