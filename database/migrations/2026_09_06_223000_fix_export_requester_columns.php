<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le demandeur d'un export ne peut pas être un UUID.
 *
 * La table est née avec `nullableUuidMorphs('requested_by')`, et c'était
 * faux : une demande vient le plus souvent d'un `User`, dont l'identifiant
 * est un **entier**. Un narrateur, lui, porte un UUID. Aucun des deux types
 * ne convient aux deux.
 *
 * La colonne devient donc une chaîne, comme `consents.subject_id` qui
 * résolvait déjà le même problème pour la même raison : « le sujet peut être
 * de plusieurs sortes ». Une convention du dépôt que la table d'exports
 * aurait dû suivre du premier coup.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exports', function (Blueprint $table): void {
            $table->dropColumn(['requested_by_type', 'requested_by_id']);
        });

        Schema::table('exports', function (Blueprint $table): void {
            $table->string('requested_by_type')->nullable()->after('status');
            $table->string('requested_by_id', 64)->nullable()->after('requested_by_type');
            $table->index(['requested_by_type', 'requested_by_id']);
        });
    }

    public function down(): void
    {
        Schema::table('exports', function (Blueprint $table): void {
            $table->dropColumn(['requested_by_type', 'requested_by_id']);
        });

        Schema::table('exports', function (Blueprint $table): void {
            $table->nullableUuidMorphs('requested_by');
        });
    }
};
