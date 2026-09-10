<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasTranslatedLabel;

enum Currency: string
{
    use HasTranslatedLabel;

    case Euro = 'EUR';
    case SwissFranc = 'CHF';

    public function symbol(): string
    {
        return match ($this) {
            self::Euro => '€',
            self::SwissFranc => 'CHF',
        };
    }
}
