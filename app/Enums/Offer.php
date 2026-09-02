<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasTranslatedLabel;

/**
 * Offre souscrite (R-2, R-3). Les prix sont des hypothèses, pas des
 * engagements : ils vivent dans `config('product.pilot')`.
 */
enum Offer: string
{
    use HasTranslatedLabel;

    case Pilot = 'pilot';
    case Core = 'core';
    case Prevente = 'prevente';
}
