<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * L'offre de bienvenue de la page d'accueil (T-141) : un code de réduction
 * contre une adresse, comme chez le leader.
 *
 * Deux réglages : la fenêtre est proposée ou non, et le pourcentage. Il doit
 * être celui du coupon Stripe `STRIPE_COUPON_WELCOME` : c'est Stripe qui fait
 * foi sur le prix, ici comme ailleurs.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('pilot.welcome_offer_enabled', true);
        $this->migrator->add('pilot.welcome_offer_discount_percent', (int) config('product.pilot.welcome_offer_discount_percent'));
    }

    public function down(): void
    {
        $this->migrator->delete('pilot.welcome_offer_enabled');
        $this->migrator->delete('pilot.welcome_offer_discount_percent');
    }
};
