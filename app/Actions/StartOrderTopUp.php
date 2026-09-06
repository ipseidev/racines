<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\Sku;
use App\Features\PhoneOptionOffer;
use App\Models\Order;
use App\Models\PhoneOption;
use App\Services\Payments\CheckoutSession;
use App\Services\Payments\CheckoutSessions;
use App\Settings\PilotSettings;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Ajouter un article à une commande déjà payée.
 *
 * Le tunnel ne se rejoue pas : la commande existe, le projet aussi, et l'on
 * ne veut ni second projet ni second narrateur. C'est donc une session de
 * paiement à une seule ligne, rattachée à la commande par ses métadonnées.
 *
 * **Ce qui se complète, et ce qui ne se complète pas.** Seuls l'option
 * téléphone et le livre numérique : les exemplaires supplémentaires se
 * commandent au moment du livre, quand on sait combien de pages il fait, et
 * l'offre elle-même ne se rachète pas.
 */
final readonly class StartOrderTopUp
{
    /** @var list<Sku> */
    public const COMPLETABLE = [Sku::PhoneOption, Sku::Ebook];

    public function __construct(private CheckoutSessions $sessions) {}

    /**
     * @return CheckoutSession|null `null` quand l'article n'est plus offrable
     *                              — plafond atteint, ou déjà présent.
     */
    public function handle(Order $order, Sku $sku): ?CheckoutSession
    {
        if (! self::isAvailableOn($order, $sku)) {
            return null;
        }

        $price = $sku->stripePriceId($order->price_variant);

        if (! is_string($price) || $price === '') {
            throw new RuntimeException("Le prix Stripe de [{$sku->value}] n'est pas configuré.");
        }

        $session = $this->sessions->create(
            customerEmail: (string) $order->user->email,
            lineItems: [['price' => $price, 'quantity' => 1]],
            metadata: [
                'order_id' => $order->id,
                'sku' => $sku->value,
            ],
            successUrl: route('initiator.orders').'?complete='.$sku->value,
            cancelUrl: route('initiator.orders'),
        );

        Log::info('checkout.top_up_opened', [
            'order_id' => $order->id,
            'sku' => $sku->value,
            'session_id' => $session->id,
        ]);

        return $session;
    }

    /**
     * Ce qu'on peut encore ajouter à cette commande.
     *
     * @return list<Sku>
     */
    public static function availableOn(Order $order): array
    {
        return array_values(array_filter(
            self::COMPLETABLE,
            fn (Sku $sku): bool => self::isAvailableOn($order, $sku),
        ));
    }

    public static function isAvailableOn(Order $order, Sku $sku): bool
    {
        if (! in_array($sku, self::COMPLETABLE, true)) {
            return false;
        }

        // Déjà acheté : on ne vend pas deux fois la même chose.
        if ($order->items()->where('sku', $sku->value)->exists()) {
            return false;
        }

        if ($sku !== Sku::PhoneOption) {
            return true;
        }

        // Le plafond du pilote vaut ici comme au tunnel : dix familles, parce
        // que c'est un humain qui rappelle. Une option vendue au-delà serait
        // une promesse qu'on ne peut pas tenir.
        $project = $order->project;

        if ($project !== null && PhoneOption::query()->where('project_id', $project->id)->exists()) {
            return false;
        }

        return PhoneOptionOffer::isOpen();
    }

    public static function priceCentsOf(Sku $sku): int
    {
        $settings = app(PilotSettings::class);

        return match ($sku) {
            Sku::PhoneOption => $settings->phone_option_price_cents,
            Sku::Ebook => $settings->ebook_price_cents,
            default => 0,
        };
    }
}
