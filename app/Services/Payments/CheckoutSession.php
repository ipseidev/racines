<?php

declare(strict_types=1);

namespace App\Services\Payments;

/**
 * Ce qu'une session de paiement rend : un identifiant et une adresse.
 */
final readonly class CheckoutSession
{
    public function __construct(
        public string $id,
        public string $url,
    ) {}
}
