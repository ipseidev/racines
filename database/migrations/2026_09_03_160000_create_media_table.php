<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La table de `spatie/laravel-medialibrary`, publiée puis reprise.
 *
 * Quatre écarts par rapport au gabarit du paquet, et chacun a sa raison.
 *
 * `uuidMorphs` plutôt que `morphs` : le gabarit suppose des clés entières, et
 * tous les modèles de domaine de ce produit ont des UUID.
 *
 * `timestampsTz` plutôt que `nullableTimestamps` : tout le schéma de ce
 * produit porte le fuseau (convention §13), et une table qui l'oublie
 * produit des dates fausses d'une heure deux fois par an.
 *
 * `jsonb` plutôt que `json` : les propriétés personnalisées portent
 * `print_ready`, `caption` et le déposant, et on les interroge — un `json`
 * en Postgres ne s'indexe pas.
 *
 * Un index sur `(model_type, model_id, collection_name)` : la galerie d'une
 * histoire est **la** requête de cette table, et le paquet ne la prévoit pas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table): void {
            $table->id();

            // `uuidMorphs` et non `morphs` : le gabarit du paquet suppose des
            // clés entières, et tous les modèles de domaine de ce produit ont
            // des UUID. Avec `morphs`, la première photo jointe échoue sur un
            // « invalid input syntax for type bigint » à l'insertion.
            $table->uuidMorphs('model');
            $table->uuid()->nullable()->unique();
            $table->string('collection_name');
            $table->string('name');
            $table->string('file_name');
            $table->string('mime_type')->nullable();
            $table->string('disk');
            $table->string('conversions_disk')->nullable();
            $table->unsignedBigInteger('size');
            $table->jsonb('manipulations');
            $table->jsonb('custom_properties');
            $table->jsonb('generated_conversions');
            $table->jsonb('responsive_images');
            $table->unsignedInteger('order_column')->nullable()->index();

            $table->timestampsTz();

            $table->index(['model_type', 'model_id', 'collection_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
