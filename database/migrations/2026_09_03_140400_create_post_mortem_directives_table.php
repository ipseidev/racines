<?php

declare(strict_types=1);

use App\Enums\PostMortemWish;
use App\Support\Database\EnumCheck;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ce que le narrateur veut qu'il advienne de ses histoires après sa mort.
 *
 * Le seul endroit du produit où l'on tranche **contre** la famille : ces
 * directives priment sur la demande des proches (doc 04 §6). Quelqu'un a le
 * droit de vouloir que ses récits disparaissent avec lui, et une famille en
 * deuil n'est pas la mieux placée pour en décider.
 *
 * Le référent est stocké **masqué et haché**, jamais en clair : on doit
 * pouvoir vérifier qu'une personne qui se présente est bien celle désignée,
 * sans conserver son carnet d'adresses. Et `consent_id` n'est pas nullable —
 * une directive sans consentement journalisé n'a aucune valeur.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_mortem_directives', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('project_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('narrator_id')->constrained()->cascadeOnDelete();

            $table->string('wishes', 32);
            $table->string('referent_name')->nullable();
            $table->string('referent_contact_masked')->nullable();
            $table->char('referent_contact_hash', 64)->nullable();

            $table->foreignUuid('consent_id')->constrained('consents')->restrictOnDelete();
            $table->timestampTz('recorded_at');

            $table->timestampsTz();

            // Une directive courante par narrateur : la dernière exprimée.
            $table->unique('narrator_id');
        });

        EnumCheck::add('post_mortem_directives', 'wishes', EnumCheck::of(PostMortemWish::class));
    }

    public function down(): void
    {
        Schema::dropIfExists('post_mortem_directives');
    }
};
