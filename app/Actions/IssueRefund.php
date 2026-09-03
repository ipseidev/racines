<?php

declare(strict_types=1);

namespace App\Actions;

use App\Audit\AuditLog;
use App\Models\Order;
use App\Services\Payments\Refund;
use App\Services\Payments\Refunds;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Rembourser une commande, total ou partiel.
 *
 * L'action **demande** le remboursement et journalise ; elle ne met pas la
 * commande à jour. C'est le webhook `charge.refunded` qui écrit
 * `orders.status` et `refunded_cents`, et c'est la seule façon d'avoir un
 * état juste : un remboursement fait depuis le tableau de bord de Stripe
 * arrive par le même chemin, et deux écritures concurrentes de la même colonne
 * divergeraient le jour où quelqu'un rembourse à la main.
 *
 * Le motif est obligatoire. Un remboursement sans motif est un mouvement
 * d'argent qu'on ne peut pas expliquer trois mois plus tard — et le
 * remboursement est justement ce que le support fait quand quelque chose s'est
 * mal passé.
 */
final readonly class IssueRefund
{
    public function __construct(private Refunds $refunds) {}

    public function handle(Order $order, ?int $amountCents, string $reason): Refund
    {
        $intent = $order->stripe_payment_intent_id;

        if (! is_string($intent) || $intent === '') {
            throw new RuntimeException('Cette commande n’a pas de paiement à rembourser.');
        }

        $remaining = $order->total_cents - $order->refunded_cents;

        if ($amountCents !== null && ($amountCents <= 0 || $amountCents > $remaining)) {
            throw new RuntimeException('Montant hors de ce qui reste remboursable.');
        }

        $refund = $this->refunds->refund($intent, $amountCents, $reason);

        AuditLog::record('refunded Order', $order, [
            'amount_cents' => $refund->amountCents,
            'requested_cents' => $amountCents,
            'reason' => $reason,
            'refund_id' => $refund->id,
        ], $order->project);

        Log::warning('checkout.refund_issued', [
            'order_id' => $order->id,
            'amount_cents' => $refund->amountCents,
            'refund_id' => $refund->id,
        ]);

        return $refund;
    }
}
