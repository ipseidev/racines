<?php

declare(strict_types=1);

use App\Enums\ProjectMemberRole;
use App\Support\Database\EnumCheck;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table purement interne : identifiant séquentiel autorisé (convention §2).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_members', function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role', 16);
            $table->timestampsTz();

            $table->unique(['project_id', 'user_id']);
        });

        EnumCheck::add('project_members', 'role', EnumCheck::of(ProjectMemberRole::class));
    }

    public function down(): void
    {
        Schema::dropIfExists('project_members');
    }
};
