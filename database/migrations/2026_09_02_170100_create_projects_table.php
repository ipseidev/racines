<?php

declare(strict_types=1);

use App\Enums\AddressForm;
use App\Enums\Cadence;
use App\Enums\Offer;
use App\Enums\ProjectStatus;
use App\Enums\PromptSlot;
use App\Enums\ValidationVariant;
use App\Support\Database\EnumCheck;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Un projet = un livre pour un narrateur principal (glossaire §2).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('owner_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('cohort_id')->nullable()->constrained('cohorts')->nullOnDelete();

            $table->string('status', 32)->default(ProjectStatus::Draft->value);
            $table->string('offer', 16);

            $table->string('address_form', 8)->default(AddressForm::Vous->value);
            $table->string('cadence', 16)->default(Cadence::Weekly->value);
            $table->smallInteger('prompt_day')->default(1);
            $table->string('prompt_slot', 16)->default(PromptSlot::Morning->value);
            $table->string('timezone', 64)->default('Europe/Paris');

            $table->timestampTz('next_prompt_at')->nullable();
            $table->timestampTz('paused_until')->nullable();

            $table->timestampTz('collection_started_at')->nullable();
            $table->timestampTz('collection_ends_at')->nullable();
            $table->timestampTz('finalization_ends_at')->nullable();

            // Copie du flag Pennant du bloc 07, conservée pour le reporting.
            $table->string('validation_variant', 16)->default(ValidationVariant::Immediate->value);

            $table->text('gift_message')->nullable();
            // La contrainte de clé étrangère est posée au bloc 04, avec `recordings`.
            $table->uuid('gift_audio_recording_id')->nullable();
            $table->timestampTz('gift_send_at')->nullable();
            $table->timestampTz('gift_sent_at')->nullable();

            // H0 : acceptation ou refus du cadeau par le narrateur.
            $table->timestampTz('accepted_at')->nullable();
            $table->timestampTz('refused_at')->nullable();
            $table->text('refusal_reason')->nullable();

            // Code famille optionnel des pages QR (D-8). Jamais stocké en clair.
            $table->string('family_code_hash', 64)->nullable();

            $table->timestampsTz();

            $table->index('owner_user_id');
            $table->index(['status', 'next_prompt_at']);
        });

        EnumCheck::add('projects', 'status', EnumCheck::of(ProjectStatus::class));
        EnumCheck::add('projects', 'offer', EnumCheck::of(Offer::class));
        EnumCheck::add('projects', 'address_form', EnumCheck::of(AddressForm::class));
        EnumCheck::add('projects', 'cadence', EnumCheck::of(Cadence::class));
        EnumCheck::add('projects', 'prompt_slot', EnumCheck::of(PromptSlot::class));
        EnumCheck::add('projects', 'validation_variant', EnumCheck::of(ValidationVariant::class));

        DB::statement('alter table projects add constraint projects_prompt_day_check check (prompt_day between 1 and 7)');
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
