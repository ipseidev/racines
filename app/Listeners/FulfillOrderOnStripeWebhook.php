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
 * Quatre événements, et c'est volontaire. `checkout.session.completed` exécute
 * la commande **si la session est payée**, `checkout.session.async_payment_succeeded`
 * l'exécute quand elle finit par l'être, `async_payment_failed` en prend acte,
 * et `charge.refunded` enregistre le remboursement. Tout le reste est ignoré
 * **sans broncher** — Stripe envoie des dizaines de types d'événements, et une
 * erreur sur un type inconnu ferait retenter le webhook indéfiniment.
 *
 * **Pourquoi la condition de paiement.** Le tunnel ne passe pas
 * `payment_method_types` : Stripe propose alors la méthode qui convertit le
 * mieux, et le compte a Klarna, Pix et BLIK actifs. Pour ces méthodes à
 * notification différée, `completed` arrive **pendant que la session est
 * encore impayée**, et le succès ne vient qu'ensuite. Exécuter sur `completed`
 * seul se tromperait deux fois d'un coup : le cadeau partirait chez un parent
 * pour une commande qui échouera, et la commande qui aboutit une heure plus
 * tard ne partirait jamais (T-167).
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
            'checkout.session.completed',
            'checkout.session.async_payment_succeeded' => $this->complete($event->payload),
            'checkout.session.async_payment_failed' => $this->paymentFailed($event->payload),
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

        // « Pas impayée » plutôt que « payée » : une commande entièrement
        // remisée sort en `no_payment_required`, et une session ancienne peut
        // ne pas porter le champ du tout. C'est l'impayé qu'on refuse, pas
        // tout ce qui n'est pas exactement `paid`.
        if (data_get($session, 'payment_status') === 'unpaid') {
            Log::info('checkout.awaiting_payment', [
                'session_id' => data_get($session, 'id'),
            ]);

            return;
        }

        $this->fulfil->handle($session);
    }

    /**
     * Le paiement différé a échoué.
     *
     * Rien à défaire : la condition de paiement fait qu'aucune commande n'a
     * été créée. On le consigne pour que le support puisse répondre à
     * quelqu'un qui croit avoir payé.
     *
     * @param  array<string, mixed>  $payload
     */
    private function paymentFailed(array $payload): void
    {
        Log::warning('checkout.async_payment_failed', [
            'session_id' => data_get($payload, 'data.object.id'),
        ]);
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
