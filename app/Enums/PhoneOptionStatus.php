<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasTranslatedLabel;

enum PhoneOptionStatus: string
{
    use HasTranslatedLabel;

    case Requested = 'requested';
    case Active = 'active';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';

    /**
     * Occupe-t-elle un créneau humain ?
     *
     * Une demande en attente compte autant qu'une option active : le créneau
     * est réservé dès qu'on l'a promis.
     */
    public function countsTowardsCap(): bool
    {
        return in_array($this, [self::Requested, self::Active], true);
    }
}
