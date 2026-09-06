<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PhoneOptionEntry;
use App\Enums\Sku;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PhoneOption;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Exécute un complément de commande payé.
 *
 * L'argent est déjà pris quand ce code s'exécute : un complément payé qui
 * n'arrive pas est un litige, pas un bogue. D'où les trois gardes.
 *
 * **Idempotent par identifiant de session** : Stripe rejoue ses webhooks,
 * parfois plusieurs fois, et deux options téléphone sur un même projet
 * voudraient dire deux appels hebdomadaires pour une seule vente.
 *
 * **Le montant vient de Stripe**, pas des réglages : le prix d'un article
 * acheté ne change pas quand celui du produit change, et c'est la session
 * payée qui fait foi.
 *
 * **Rien n'est deviné** : une commande introuvable ne crée rien, elle se
 * consigne pour le support. Comme pour la commande initiale (T-169).
 */
final readonly class FulfillOrderTopUp
{
    /**
     * @param  array<string, mixed>  $session
     */
    public function handle(array $session): ?OrderItem
    {
        $sessionId = (string) ($session['id'] ?? '');
        $orderId = (string) data_get($session, 'metadata.order_id');
        $skuValue = (string) data_get($session, 'metadata.sku');

        if ($sessionId === '' || ! Str::isUuid($orderId) || $skuValue === '') {
            return null;
        }

        $sku = Sku::tryFrom($skuValue);

        if ($sku === null || ! in_array($sku, StartOrderTopUp::COMPLETABLE, true)) {
            Log::warning('checkout.top_up_unknown_sku', ['session_id' => $sessionId, 'sku' => $skuValue]);

            return null;
        }

        $existing = OrderItem::query()->where('stripe_checkout_session_id', $sessionId)->first();

        if ($existing instanceof OrderItem) {
            Log::info('checkout.top_up_replayed', ['session_id' => $sessionId]);

            return $existing;
        }

        $order = Order::query()->whereKey($orderId)->first();

        if (! $order instanceof Order) {
            Log::warning('checkout.top_up_orphan', ['session_id' => $sessionId, 'order_id' => $orderId]);

            return null;
        }

        $cents = (int) ($session['amount_total'] ?? 0);

        return DB::transaction(function () use ($order, $sku, $cents, $sessionId): OrderItem {
            $item = new OrderItem([
                'sku' => $sku,
                'quantity' => 1,
                'unit_cents' => $cents,
                'stripe_price_id' => $sku->stripePriceId($order->price_variant),
                'stripe_checkout_session_id' => $sessionId,
            ]);

            $item->order()->associate($order);
            $item->save();

            $order->total_cents += $cents;
            $order->save();

            if ($sku === Sku::PhoneOption && $order->project !== null) {
                // `complement` et non `checkout` : mélanger les deux
                // gonflerait le taux d'attache au tunnel, qui est le nombre
                // sur lequel se décide l'automatisation en Phase 2 (D-9).
                $option = new PhoneOption(['entry' => PhoneOptionEntry::Complement]);
                $option->project()->associate($order->project);
                $option->orderItem()->associate($item);
                $option->save();
            }

            Log::info('checkout.top_up_fulfilled', [
                'order_id' => $order->id,
                'sku' => $sku->value,
                'cents' => $cents,
            ]);

            return $item;
        });
    }
}
