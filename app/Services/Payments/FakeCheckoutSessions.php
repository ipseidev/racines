<?php

declare(strict_types=1);

namespace App\Services\Payments;

use Illuminate\Support\Str;

/**
 * Les sessions de paiement, en mémoire.
 *
 * Elle garde ce qu'on lui a demandé : c'est ainsi qu'on vérifie que le tunnel
 * envoie les bons identifiants de prix et les bonnes quantités, sans appeler
 * Stripe.
 */
final class FakeCheckoutSessions implements CheckoutSessions
{
    /** @var list<array<string, mixed>> */
    private array $created = [];

    /**
     * @param  list<array{price: string, quantity: int}>  $lineItems
     * @param  array<string, string>  $metadata
     */
    public function create(
        string $customerEmail,
        array $lineItems,
        array $metadata,
        string $successUrl,
        string $cancelUrl,
    ): CheckoutSession {
        $id = 'cs_test_'.Str::random(24);

        $this->created[] = [
            'id' => $id,
            'customer_email' => $customerEmail,
            'line_items' => $lineItems,
            'metadata' => $metadata,
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
        ];

        return new CheckoutSession(id: $id, url: 'https://checkout.stripe.test/'.$id);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function last(): ?array
    {
        return $this->created === [] ? null : $this->created[count($this->created) - 1];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function all(): array
    {
        return $this->created;
    }
}
