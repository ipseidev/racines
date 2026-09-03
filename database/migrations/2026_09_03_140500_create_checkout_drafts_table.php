<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Un tunnel d'achat commencé et pas fini.
 *
 * Six étapes, dont une création de compte : quelqu'un qui abandonne à la
 * cinquième ne doit pas tout ressaisir. Le brouillon vit sept jours, puis
 * disparaît — il contient le prénom d'un parent et un numéro de téléphone,
 * et ce n'est pas une donnée qu'on garde « au cas où ».
 *
 * `payload` est un jsonb plutôt que vingt colonnes : la forme du tunnel
 * bougera au pilote, et une migration par ajustement d'étape serait
 * insupportable. Ce qui doit être requêtable est extrait à l'exécution.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checkout_drafts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->smallInteger('step')->default(1);
            $table->jsonb('payload');
            $table->smallInteger('price_variant')->nullable();

            $table->timestampTz('expires_at');
            $table->timestampsTz();

            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checkout_drafts');
    }
};
