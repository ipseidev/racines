<?php

declare(strict_types=1);

namespace App\Services\Payments;

/**
 * Le remboursement tel que le prestataire l'a accepté.
 *
 * Le **montant rendu** et non celui demandé : Stripe peut rembourser moins que
 * demandé si une partie l'a déjà été, et c'est ce qu'il dit qui compte.
 */
final readonly class Refund
{
    public function __construct(
        public string $id,
        public int $amountCents,
        public string $status,
    ) {}
}
