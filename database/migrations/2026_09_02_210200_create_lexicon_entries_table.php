<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le lexique des noms propres d'un projet.
 *
 * Sans lui, « Kerhostin » devient « Ker Austin » et le petit-fils qui lit le
 * livre ne reconnaît pas le village de sa grand-mère. Le lexique sert deux
 * fois : en vocabulaire donné au fournisseur ASR avant la transcription, et
 * en correction appliquée au texte après.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lexicon_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('project_id')->constrained()->cascadeOnDelete();

            $table->string('term');
            $table->string('replacement')->nullable();
            $table->string('notes')->nullable();

            $table->string('created_by_type')->nullable();
            $table->string('created_by_id', 64)->nullable();

            $table->timestampsTz();

            $table->unique(['project_id', 'term']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lexicon_entries');
    }
};
