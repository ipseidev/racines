<?php

declare(strict_types=1);

use App\Enums\Sku;
use App\Support\Database\EnumCheck;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ce qu'une commande contient, ligne par ligne.
 *
 * `unit_cents` est **copié** de Stripe au moment du paiement, pas lu dans les
 * réglages à l'affichage : le prix d'une commande passée ne change jamais,
 * même quand celui du produit change. Une facture qui se réécrit n'est pas
 * une facture.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('order_id')->constrained()->cascadeOnDelete();

            $table->string('sku', 32);
            $table->smallInteger('quantity')->default(1);
            $table->integer('unit_cents');
            $table->string('stripe_price_id')->nullable();
            $table->jsonb('metadata')->nullable();

            $table->timestampsTz();

            $table->index(['order_id', 'sku']);
        });

        EnumCheck::add('order_items', 'sku', EnumCheck::of(Sku::class));
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
