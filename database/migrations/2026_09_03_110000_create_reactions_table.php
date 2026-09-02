<?php

declare(strict_types=1);

use App\Enums\ReactionType;
use App\Support\Database\EnumCheck;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ce qu'un proche répond à une histoire.
 *
 * Unique sur `(story_id, family_member_id, type)` : un cœur donné deux fois
 * reste un cœur. Le narrateur n'a pas à distinguer un enthousiasme d'un
 * double-clic, et une notification par tap serait du harcèlement.
 *
 * `comment` est court par construction (280 caractères) : le produit demande
 * un mot, pas une lettre — et un mot arrive, là où une lettre reste en
 * brouillon.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reactions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('story_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('family_member_id')->constrained()->cascadeOnDelete();

            $table->string('type', 16);
            $table->string('comment', 280)->nullable();

            $table->timestampTz('created_at')->nullable();
            $table->timestampTz('updated_at')->nullable();

            $table->unique(['story_id', 'family_member_id', 'type']);
            $table->index(['story_id', 'created_at']);
        });

        EnumCheck::add('reactions', 'type', EnumCheck::of(ReactionType::class));
    }

    public function down(): void
    {
        Schema::dropIfExists('reactions');
    }
};
