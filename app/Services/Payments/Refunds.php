<?php

declare(strict_types=1);

namespace App\Services\Payments;

/**
 * Rembourser, total ou partiel.
 *
 * Un port, pour la même raison que `CheckoutSessions` (T-105) : le SDK de
 * Stripe a son propre transport, que `Http::preventStrayRequests()` n'atteint
 * pas. Sans port, un test qui aurait oublié un doublon aurait pu **rembourser
 * un vrai paiement** — l'erreur la plus coûteuse que ce dépôt puisse commettre.
 *
 * Le motif est un paramètre obligatoire et non un commentaire optionnel : un
 * remboursement sans motif est un mouvement d'argent qu'on ne peut pas
 * expliquer trois mois plus tard.
 */
interface Refunds
{
    /**
     * @param  int|null  $amountCents  `null` pour un remboursement total.
     */
    public function refund(string $paymentIntentId, ?int $amountCents, string $reason): Refund;
}
