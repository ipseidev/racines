<?php

declare(strict_types=1);

use App\Enums\TokenIssuedReason;
use App\Enums\TokenType;
use App\Support\Database\EnumCheck;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tous les liens du produit passent par cette table.
 *
 * Le jeton n'y est jamais en clair : seule son empreinte SHA-256 est stockée,
 * et c'est sur elle que porte l'index unique (doc 04 §12). Une fuite de la
 * base ne donne donc aucun lien utilisable.
 *
 * `subject_id` est une chaîne : le sujet peut être une histoire, un projet, un
 * narrateur ou un proche (uuid) comme un utilisateur (identifiant séquentiel).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('access_tokens', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('type', 32);
            $table->char('token_hash', 64)->unique();

            $table->string('subject_type');
            $table->string('subject_id', 64);

            // Actions autorisées, par exemple ["record", "decide_share"].
            $table->jsonb('scope')->nullable();

            $table->timestampTz('expires_at')->nullable();
            $table->boolean('single_use')->default(false);
            $table->timestampTz('used_at')->nullable();
            $table->timestampTz('revoked_at')->nullable();
            $table->uuid('replaced_by_token_id')->nullable();

            $table->string('issued_by_type')->nullable();
            $table->string('issued_by_id', 64)->nullable();
            $table->string('issued_reason', 32)->default(TokenIssuedReason::Initial->value);

            $table->timestampTz('last_used_at')->nullable();
            $table->integer('use_count')->default(0);

            $table->timestampTz('created_at')->nullable();

            $table->index(['subject_type', 'subject_id', 'type']);
            $table->index('expires_at');
        });

        // La clé étrangère est posée après la création : Postgres exige que la
        // clé primaire référencée existe déjà, et une table ne peut pas se
        // référencer elle-même dans son propre CREATE.
        Schema::table('access_tokens', function (Blueprint $table): void {
            $table->foreign('replaced_by_token_id')->references('id')->on('access_tokens')->nullOnDelete();
        });

        EnumCheck::add('access_tokens', 'type', EnumCheck::of(TokenType::class));
        EnumCheck::add('access_tokens', 'issued_reason', EnumCheck::of(TokenIssuedReason::class));
    }

    public function down(): void
    {
        Schema::dropIfExists('access_tokens');
    }
};
