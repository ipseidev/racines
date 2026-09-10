<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasTranslatedLabel;

/**
 * Le marché d'une locale : le pays dont on suit les usages de formatage et
 * la monnaie. Les prix restent en euros partout tant que la grille suisse
 * n'est pas décidée (T-238) ; `currency()` dit ce que le marché attend, pas
 * ce qui est facturé aujourd'hui.
 */
enum Market: string
{
    use HasTranslatedLabel;

    case France = 'FR';
    case Italy = 'IT';
    case Spain = 'ES';
    case Switzerland = 'CH';

    public function currency(): Currency
    {
        return match ($this) {
            self::Switzerland => Currency::SwissFranc,
            default => Currency::Euro,
        };
    }
}
