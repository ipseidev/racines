<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasTranslatedLabel;

/**
 * Phase du pilote à laquelle appartient une cohorte (R-8, bloc 17).
 */
enum CohortPhase: string
{
    use HasTranslatedLabel;

    case Phase0A = '0A';
    case Phase0B = '0B';
    case Launch = 'launch';
}
