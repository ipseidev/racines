<?php

declare(strict_types=1);

use App\Enums\SupportTicketKind;
use App\Enums\SupportTicketStatus;
use App\Support\Database\EnumCheck;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Les tickets que le produit ouvre **de lui-même**.
 *
 * Une personne de 82 ans qui n'arrive pas à autoriser son micro n'écrit pas au
 * support : elle abandonne, et personne ne sait pourquoi. C'est donc au
 * produit de lever la main à sa place.
 *
 * `payload` porte le contexte sans donnée personnelle en clair : des
 * identifiants et des compteurs, de quoi comprendre sans exposer.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_tickets', function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('project_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('story_id')->nullable()->constrained()->nullOnDelete();

            $table->string('kind', 48);
            $table->string('status', 16)->default(SupportTicketStatus::Open->value);
            $table->jsonb('payload')->nullable();

            $table->timestampTz('opened_at');
            $table->timestampTz('closed_at')->nullable();
            $table->foreignId('closed_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestampsTz();

            $table->index(['status', 'kind']);
            $table->index(['project_id', 'kind']);
        });

        EnumCheck::add('support_tickets', 'kind', EnumCheck::of(SupportTicketKind::class));
        EnumCheck::add('support_tickets', 'status', EnumCheck::of(SupportTicketStatus::class));
    }

    public function down(): void
    {
        Schema::dropIfExists('support_tickets');
    }
};
