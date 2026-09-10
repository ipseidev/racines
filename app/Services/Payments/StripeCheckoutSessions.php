<?php

declare(strict_types=1);

namespace App\Services\Payments;

use App\Support\Locales;
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
            // La page de Stripe parle la langue de celle qu'on quitte : un
            // tunnel en italien qui s'achève sur un formulaire français perd
            // la personne au moment où elle sort sa carte. Stripe ne connaît
            // que la langue, pas le marché.
            'locale' => Locales::current()->stripe(),
            /*
             * Ce qui suit vient du Checkout Studio (2026-09-10). Les quatre
             * premiers sont les valeurs par défaut de Stripe, écrites ici
             * pour que la page reste celle qu'on a réglée le jour où Stripe
             * changera ses défauts.
             */
            'ui_mode' => 'hosted_page',
            'origin_context' => 'web',
            'billing_address_collection' => 'auto',
            'submit_type' => 'auto',
            // Le téléphone de l'acheteur, que le tunnel ne demande pas : il
            // ne collecte que celui du narrateur, à qui partent les SMS.
            'phone_number_collection' => ['enabled' => true],
            /*
             * La TVA calculée par Stripe. Sans effet tant qu'aucune
             * immatriculation n'est active ; le jour où l'une le sera, l'euro
             * est inféré toutes taxes comprises et les prix annoncés ne
             * bougeront pas — la taxe s'extrait du montant au lieu de s'y
             * ajouter.
             */
            'automatic_tax' => ['enabled' => true],
        ];

        /*
         * L'un ou l'autre, jamais les deux : « You may only specify one of
         * these parameters: allow_promotion_codes, discounts ».
         *
         * Un code de bienvenue posé chez nous gagne : c'est celui dont on
         * sait à qui il appartient et s'il a déjà servi (T-141), et le
         * récapitulatif en a déjà annoncé le montant. Sinon la page de
         * Stripe ouvre son champ, qui sert les codes promotionnels créés
         * là-bas — ceux qu'aucune ligne de `leads` ne porte.
         */
        if ($discounts !== []) {
            $parameters['discounts'] = $discounts;
        } else {
            $parameters['allow_promotion_codes'] = true;
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
