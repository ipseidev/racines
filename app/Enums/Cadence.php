<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasTranslatedLabel;

/**
 * Rythme d'envoi des questions (PRD §5).
 */
enum Cadence: string
{
    use HasTranslatedLabel;

    case Weekly = 'weekly';
    case Biweekly = 'biweekly';

    public function weeks(): int
    {
        return match ($this) {
            self::Weekly => 1,
            self::Biweekly => 2,
        };
    }
}
