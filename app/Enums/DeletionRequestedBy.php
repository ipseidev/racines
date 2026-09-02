<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasTranslatedLabel;

/**
 * Qui a demandé la suppression définitive d'une histoire (R-4).
 */
enum DeletionRequestedBy: string
{
    use HasTranslatedLabel;

    case Narrator = 'narrator';
    case Mandate = 'mandate';
    case Admin = 'admin';
}
