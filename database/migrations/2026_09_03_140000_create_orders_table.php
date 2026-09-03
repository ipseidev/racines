<?php

declare(strict_types=1);

use App\Enums\OrderStatus;
use App\Support\Database\EnumCheck;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Les commandes, et ce qu'elles engagent.
 *
 * `stripe_checkout_session_id` est **unique**, et c'est ce qui rend
 * l'exécution idempotente : Stripe rejoue ses webhooks, parfois plusieurs
 * fois, et un projet créé en double serait un projet de trop dans la vie
 * d'une famille.
 *
 * `withdrawal_deadline_at` est calculé à l'encaissement (payé + 14 jours) et
 * **stocké**, pas recalculé à la lecture : le délai légal se compte à partir
 * d'un fait daté, et une règle qui change ne doit pas rétroagir sur des
 * commandes passées.
 *
 * `price_variant` garde le prix de prévente réellement vu par l'acheteur, en centimes.
 * Sans lui, H3 ne se mesure pas : on saurait combien on a vendu, pas à quel
 * prix les gens ont dit oui.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('project_id')->nullable()->constrained()->nullOnDelete();

            $table->string('stripe_checkout_session_id')->unique();
            $table->string('stripe_payment_intent_id')->nullable();
            $table->string('stripe_invoice_url')->nullable();

            $table->string('status', 24)->default(OrderStatus::Pending->value);
            $table->string('currency', 3)->default('eur');
            $table->integer('subtotal_cents');
            $table->integer('total_cents');
            $table->integer('refunded_cents')->default(0);

            // 9 900 ou 12 900 : le prix de prévente vu par cet acheteur, en
            // **centimes**, comme tous les prix du produit. Le brouillon le
            // porte dans la même unité : deux colonnes homonymes dans deux
            // unités différentes se paient à la première facture.
            $table->smallInteger('price_variant')->nullable();

            $table->timestampTz('paid_at')->nullable();
            $table->timestampTz('withdrawal_deadline_at')->nullable();
            // Posé si l'acheteur a demandé le démarrage immédiat : c'est ce
            // qui justifie de retenir une part en cas de rétractation.
            $table->timestampTz('service_started_at')->nullable();

            $table->timestampsTz();

            $table->index(['user_id', 'status']);
            $table->index('withdrawal_deadline_at');
        });

        EnumCheck::add('orders', 'status', EnumCheck::of(OrderStatus::class));
        EnumCheck::add('orders', 'price_variant', ['9900', '12900'], nullable: true);
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
