<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\Sku;
use App\Features\PhoneOptionOffer;
use App\Models\CheckoutDraft;
use App\Models\User;
use App\Services\Payments\CheckoutSession;
use App\Services\Payments\CheckoutSessions;
use App\Settings\PilotSettings;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Ouvre la session de paiement, à partir du brouillon.
 *
 * Les lignes viennent des **identifiants de prix Stripe**, jamais de montants
 * calculés ici : c'est Stripe qui fait foi sur le prix, et un montant envoyé
 * par le client serait un montant négociable. Le lien entre un article et son
 * prix vit dans `config/services.php`.
 *
 * `metadata.draft_id` est ce qui permettra au webhook de retrouver le
 * brouillon : sans lui, une session payée serait un paiement orphelin.
 */
final readonly class StartStripeCheckout
{
    public function __construct(private CheckoutSessions $sessions) {}

    public function handle(CheckoutDraft $draft, User $buyer): CheckoutSession
    {
        $items = self::lineItemsFor($draft);

        if ($items === []) {
            throw new RuntimeException('Aucun article vendable : les prix Stripe ne sont pas configurés.');
        }

        $session = $this->sessions->create(
            customerEmail: $buyer->email,
            lineItems: $items,
            metadata: [
                'draft_id' => $draft->id,
                'user_id' => (string) $buyer->id,
                'price_variant' => (string) ($draft->price_variant ?? ''),
            ],
            successUrl: route('checkout.thanks').'?session_id={CHECKOUT_SESSION_ID}',
            cancelUrl: route('checkout.show', ['step' => 6]),
        );

        $draft->merge(['stripe_checkout_session_id' => $session->id]);

        Log::info('checkout.session_opened', [
            'draft_id' => $draft->id,
            'session_id' => $session->id,
            'items' => count($items),
        ]);

        return $session;
    }

    /**
     * @return list<array{price: string, quantity: int}>
     */
    public static function lineItemsFor(CheckoutDraft $draft): array
    {
        $settings = app(PilotSettings::class);
        $items = [];

        // L'offre elle-même : pilote, ou prévente selon le mode. Jamais les
        // deux, et jamais aucune.
        $main = $settings->isPrevente() ? Sku::CorePrevente : Sku::Pilot;
        $mainPrice = $main->stripePriceId($draft->price_variant);

        if ($mainPrice !== null) {
            $items[] = ['price' => $mainPrice, 'quantity' => 1];
        }

        $extra = (int) $draft->value('extra_copies', 0);
        $extraPrice = Sku::ExtraCopy->stripePriceId();

        if ($extra > 0 && $extraPrice !== null) {
            $items[] = ['price' => $extraPrice, 'quantity' => $extra];
        }

        // Revérifié ici : entre l'étape 5 et le paiement, une autre famille a
        // pu prendre le dernier créneau.
        $phonePrice = Sku::PhoneOption->stripePriceId();

        if ($draft->value('phone_option') === true && PhoneOptionOffer::isOpen() && $phonePrice !== null) {
            $items[] = ['price' => $phonePrice, 'quantity' => 1];
        }

        return $items;
    }
}
