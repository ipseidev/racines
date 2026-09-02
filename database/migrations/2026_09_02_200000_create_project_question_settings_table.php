<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ce que l'Initiateur·rice change au corpus pour un projet donné : exclure une
 * question, en avancer une autre (annexe A, règle 3).
 *
 * Le corpus lui-même n'est pas modifié : il est partagé, éditorialisé, et sert
 * de référence aux analyses. Les préférences vivent ici.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_question_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('project_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('question_id')->constrained()->cascadeOnDelete();
            $table->boolean('excluded')->default(false);
            $table->integer('custom_order')->nullable();
            $table->timestampsTz();

            $table->unique(['project_id', 'question_id']);
            $table->index(['project_id', 'excluded']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_question_settings');
    }
};
