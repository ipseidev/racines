<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasTranslatedLabel;

/**
 * Où en est une commande.
 *
 * `partially_refunded` existe parce qu'un remboursement partiel est le cas
 * réel le plus fréquent : on rembourse l'option téléphone qu'on n'a pas
 * assurée, pas la commande entière.
 */
enum OrderStatus: string
{
    use HasTranslatedLabel;

    case Pending = 'pending';
    case Paid = 'paid';
    case Refunded = 'refunded';
    case PartiallyRefunded = 'partially_refunded';
    case Cancelled = 'cancelled';

    public function isPaid(): bool
    {
        return in_array($this, [self::Paid, self::PartiallyRefunded], true);
    }
}
