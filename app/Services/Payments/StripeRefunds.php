<?php

declare(strict_types=1);

namespace App\Services\Payments;

use Stripe\StripeClient;

/**
 * Le port `Refunds`, servi par Stripe.
 *
 * Le webhook `charge.refunded` reste la source de vérité sur l'état de la
 * commande : c'est lui qui écrit `orders.status` et `refunded_cents`. Cet
 * adaptateur **demande** le remboursement, il ne met pas la commande à jour
 * — sinon deux chemins écriraient la même colonne, et ils divergeraient le
 * jour où un remboursement est fait depuis le tableau de bord de Stripe.
 */
final class StripeRefunds implements Refunds
{
    private ?StripeClient $client = null;

    public function refund(string $paymentIntentId, ?int $amountCents, string $reason): Refund
    {
        $payload = [
            'payment_intent' => $paymentIntentId,
            // Le motif nous appartient : les motifs normalisés de Stripe
            // (`duplicate`, `fraudulent`, `requested_by_customer`) ne
            // décrivent pas « le narrateur a préféré ne pas participer ».
            'metadata' => ['reason' => mb_substr($reason, 0, 500)],
        ];

        if ($amountCents !== null) {
            $payload['amount'] = $amountCents;
        }

        $refund = $this->client()->refunds->create($payload);

        return new Refund(
            id: (string) $refund->id,
            amountCents: (int) $refund->amount,
            status: (string) $refund->status,
        );
    }

    private function client(): StripeClient
    {
        return $this->client ??= new StripeClient((string) config('cashier.secret'));
    }
}
