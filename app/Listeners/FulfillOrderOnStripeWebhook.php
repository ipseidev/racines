<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Actions\FulfillOrder;
use App\Enums\OrderStatus;
use App\Enums\ProjectStatus;
use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Events\WebhookReceived;

/**
 * Ce que Stripe nous dit, et ce qu'on en fait.
 *
 * Deux événements seulement, et c'est volontaire : `checkout.session.completed`
 * exécute la commande, `charge.refunded` en enregistre le remboursement. Tout
 * le reste est ignoré **sans broncher** — Stripe envoie des dizaines de types
 * d'événements, et une erreur sur un type inconnu ferait retenter le webhook
 * indéfiniment.
 *
 * La signature est vérifiée par Cashier avant que cet écouteur soit appelé :
 * un événement non signé n'arrive jamais ici.
 */
final readonly class FulfillOrderOnStripeWebhook
{
    public function __construct(private FulfillOrder $fulfil) {}

    public function handle(WebhookReceived $event): void
    {
        $type = (string) ($event->payload['type'] ?? '');

        match ($type) {
            'checkout.session.completed' => $this->complete($event->payload),
            'charge.refunded' => $this->refund($event->payload),
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function complete(array $payload): void
    {
        $session = (array) data_get($payload, 'data.object', []);

        $this->fulfil->handle($session);
    }

    /**
     * Un remboursement, total ou partiel.
     *
     * Le partiel est le cas réel le plus fréquent : on rembourse l'option
     * téléphone qu'on n'a pas assurée, pas la commande entière. Et un
     * remboursement **total avant acceptation** annule le projet — inutile de
     * laisser un cadeau en attente que plus personne ne paie.
     *
     * @param  array<string, mixed>  $payload
     */
    private function refund(array $payload): void
    {
        $charge = (array) data_get($payload, 'data.object', []);
        $intent = data_get($charge, 'payment_intent');

        if (! is_string($intent) || $intent === '') {
            return;
        }

        $order = Order::query()->where('stripe_payment_intent_id', $intent)->first();

        if (! $order instanceof Order) {
            Log::warning('checkout.refund_unknown_order', ['payment_intent' => $intent]);

            return;
        }

        $refunded = (int) data_get($charge, 'amount_refunded', 0);
        $order->refunded_cents = $refunded;
        $order->status = $refunded >= $order->total_cents
            ? OrderStatus::Refunded
            : OrderStatus::PartiallyRefunded;
        $order->save();

        $project = $order->project;

        if ($order->status === OrderStatus::Refunded
            && $project !== null
            && $project->accepted_at === null) {
            $project->status = ProjectStatus::Cancelled;
            $project->save();
        }

        Log::warning('checkout.refunded', [
            'order_id' => $order->id,
            'refunded_cents' => $refunded,
            'status' => $order->status->value,
        ]);
    }
}
