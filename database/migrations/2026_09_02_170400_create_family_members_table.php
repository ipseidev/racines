<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Les proches n'ont pas de compte : ils écoutent par jeton `listen_project`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('family_members', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invited_by_user_id')->constrained('users')->cascadeOnDelete();

            $table->string('display_name');
            $table->string('relationship')->nullable();
            $table->string('email')->nullable();
            $table->string('phone_e164', 20)->nullable();

            // Dépôt de photos et de réponses écrites, ouvert au bloc 12.
            $table->boolean('can_contribute')->default(false);

            $table->timestampTz('invited_at')->nullable();
            $table->timestampTz('first_seen_at')->nullable();
            $table->timestampTz('removed_at')->nullable();

            $table->timestampsTz();

            $table->index('project_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('family_members');
    }
};
