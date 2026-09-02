<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Qui peut écouter une histoire à visibilité restreinte.
 *
 * Une liste blanche, jamais une liste noire : c'est le narrateur qui désigne
 * les personnes à qui il confie un souvenir, et non celles à qui il le
 * refuse. La table n'a de sens que pour `visibility = restricted` ; ailleurs,
 * elle est vide, et `SetStoryVisibility` s'en assure.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('story_visibility_family_members', function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('story_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('family_member_id')->constrained()->cascadeOnDelete();
            // Posé par la base : `sync()` n'écrit pas d'horodatage, et la
            // date à laquelle un accès a été ouvert vaut d'être gardée.
            $table->timestampTz('created_at')->useCurrent();

            // Un proche autorisé une fois, pas deux.
            $table->unique(['story_id', 'family_member_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('story_visibility_family_members');
    }
};
