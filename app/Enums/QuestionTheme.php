<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasTranslatedLabel;

/**
 * Thèmes du corpus de questions (annexe A).
 */
enum QuestionTheme: string
{
    use HasTranslatedLabel;

    case Childhood = 'childhood';
    case FamilyOrigins = 'family_origins';
    case Youth = 'youth';
    case Work = 'work';
    case Love = 'love';
    case Places = 'places';
    case Joys = 'joys';
    case Hardships = 'hardships';
    case BeliefsValues = 'beliefs_values';
    case Legacy = 'legacy';
}
