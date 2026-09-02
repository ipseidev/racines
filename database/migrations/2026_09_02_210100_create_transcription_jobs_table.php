<?php

declare(strict_types=1);

use App\Enums\TranscriptionStatus;
use App\Support\Database\EnumCheck;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Une demande de transcription auprès d'un fournisseur.
 *
 * Table à part des `transcripts` : une transcription peut échouer, être
 * réessayée, changer de fournisseur, sans qu'aucun texte n'existe encore. La
 * garder séparée permet aussi de mesurer les délais réels, ce que le banc
 * d'essai ASR compare.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transcription_jobs', function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('recording_id')->constrained()->cascadeOnDelete();

            $table->string('provider', 32);
            $table->string('provider_job_id')->nullable();
            $table->string('status', 16)->default(TranscriptionStatus::Queued->value);
            $table->smallInteger('attempts')->default(0);

            $table->timestampTz('submitted_at')->nullable();
            $table->timestampTz('completed_at')->nullable();
            $table->text('error')->nullable();

            $table->timestampsTz();

            $table->index(['status', 'submitted_at']);
            $table->index('provider_job_id');
        });

        EnumCheck::add('transcription_jobs', 'status', EnumCheck::of(TranscriptionStatus::class));
    }

    public function down(): void
    {
        Schema::dropIfExists('transcription_jobs');
    }
};
