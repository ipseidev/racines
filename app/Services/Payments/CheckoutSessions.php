<?php

declare(strict_types=1);

namespace App\Services\Payments;

/**
 * L'ouverture d'une session de paiement, réduite à ce dont le tunnel a besoin.
 *
 * Même parti que pour le modèle de langage (T-70) : le SDK de Stripe a son
 * propre transport, que `Http::preventStrayRequests()` n'atteint pas, et le
 * tunnel n'a aucune raison de connaître l'arborescence d'objets d'un
 * prestataire. Le port rend les six étapes éprouvables sans réseau.
 */
interface CheckoutSessions
{
    /**
     * @param  list<array{price: string, quantity: int}>  $lineItems
     * @param  array<string, string>  $metadata
     * @param  list<array{coupon: string}>  $discounts  Des identifiants de
     *                                                  coupons Stripe, jamais
     *                                                  des montants (T-141).
     */
    public function create(
        string $customerEmail,
        array $lineItems,
        array $metadata,
        string $successUrl,
        string $cancelUrl,
        array $discounts = [],
    ): CheckoutSession;
}
