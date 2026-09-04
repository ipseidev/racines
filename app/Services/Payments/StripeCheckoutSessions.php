<?php

declare(strict_types=1);

namespace App\Services\Payments;

use Stripe\StripeClient;

/**
 * Le port `CheckoutSessions`, servi par Stripe.
 *
 * `mode: payment` et non `subscription` : le produit vend des achats uniques.
 * Le paiement se fait sur les pages hébergées de Stripe — aucun numéro de
 * carte ne traverse jamais ce serveur, ce qui est la seule façon sérieuse de
 * ne pas avoir à le protéger.
 */
final class StripeCheckoutSessions implements CheckoutSessions
{
    private ?StripeClient $client = null;

    /**
     * @param  list<array{price: string, quantity: int}>  $lineItems
     * @param  array<string, string>  $metadata
     * @param  list<array{coupon: string}>  $discounts
     */
    public function create(
        string $customerEmail,
        array $lineItems,
        array $metadata,
        string $successUrl,
        string $cancelUrl,
        array $discounts = [],
    ): CheckoutSession {
        $parameters = [
            'mode' => 'payment',
            'customer_email' => $customerEmail,
            'line_items' => $lineItems,
            'metadata' => $metadata,
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'locale' => 'fr',
        ];

        // Un coupon posé par nous, et pas de champ « code promo » sur la page
        // de Stripe : le code se saisit chez nous, où l'on sait à qui il
        // appartient et s'il a déjà servi.
        if ($discounts !== []) {
            $parameters['discounts'] = $discounts;
        }

        $session = $this->client()->checkout->sessions->create($parameters);

        return new CheckoutSession(
            id: (string) $session->id,
            url: (string) $session->url,
        );
    }

    private function client(): StripeClient
    {
        return $this->client ??= new StripeClient((string) config('cashier.secret'));
    }
}
