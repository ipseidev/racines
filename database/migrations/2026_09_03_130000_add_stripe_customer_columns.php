<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Les colonnes client de Cashier, sur `users`.
 *
 * Seule migration retenue des cinq que Cashier publie : le produit vend des
 * **achats uniques**, jamais des abonnements. Les quatre tables
 * d'abonnement seraient des fantômes vides, non documentés dans l'annexe B
 * (décision T-104) — si un abonnement apparaît un jour, on les republiera.
 *
 * `trial_ends_at` reste : Cashier le lit dans des chemins de code qu'on ne
 * peut pas retirer sans forker la bibliothèque.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('stripe_id')->nullable()->index();
            $table->string('pm_type')->nullable();
            $table->string('pm_last_four', 4)->nullable();
            $table->timestampTz('trial_ends_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['stripe_id']);
            $table->dropColumn(['stripe_id', 'pm_type', 'pm_last_four', 'trial_ends_at']);
        });
    }
};
