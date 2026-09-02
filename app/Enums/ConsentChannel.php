<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasTranslatedLabel;

/**
 * Par où le consentement a été recueilli. `phone` exige un opérateur nommé.
 */
enum ConsentChannel: string
{
    use HasTranslatedLabel;

    case Web = 'web';
    case Phone = 'phone';
    case Admin = 'admin';
}
