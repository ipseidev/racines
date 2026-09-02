<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasTranslatedLabel;

/**
 * Portée de visibilité d'une histoire validée (R-4).
 */
enum StoryVisibility: string
{
    use HasTranslatedLabel;

    case AllFamily = 'all_family';
    case Restricted = 'restricted';
    case BookOnly = 'book_only';
}
