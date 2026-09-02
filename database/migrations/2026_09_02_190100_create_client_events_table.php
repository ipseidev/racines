<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ce que le navigateur du narrateur rapporte de sa séance.
 *
 * Micro refusé, page cachée, brouillon repris, alerte de durée : c'est la
 * seule façon de mesurer le taux d'échec de capture avant confirmation, que le
 * doc 04 §11 fixe sous 2 %. Le contenu du payload ne porte aucune donnée
 * personnelle en clair — ni jeton, ni coordonnée.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('story_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event', 64);
            $table->jsonb('payload')->nullable();
            $table->timestampTz('created_at')->nullable();

            $table->index(['event', 'created_at']);
            $table->index('story_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_events');
    }
};
