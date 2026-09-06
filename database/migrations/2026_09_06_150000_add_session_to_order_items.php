<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * L'identifiant de session Stripe d'un complément de commande (T-184).
 *
 * **Unique**, et c'est tout l'objet : Stripe rejoue ses webhooks, et deux
 * options téléphone sur un même projet voudraient dire deux appels
 * hebdomadaires pour une seule vente. L'idempotence de la commande initiale
 * repose sur la même colonne côté `orders` ; celle d'un complément ne pouvait
 * pas s'y appuyer, puisque la commande existe déjà.
 *
 * Nulle pour les lignes nées du tunnel : elles sont couvertes par
 * l'idempotence de la commande.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table): void {
            $table->string('stripe_checkout_session_id')->nullable()->unique()->after('stripe_price_id');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table): void {
            $table->dropColumn('stripe_checkout_session_id');
        });
    }
};
