<?php

declare(strict_types=1);

use App\Enums\Channel;
use App\Enums\OtpPurpose;
use App\Support\Database\EnumCheck;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Codes à usage unique pour les actes sensibles (doc 04 §12).
 *
 * Le code n'est pas stocké : seule son empreinte, salée par l'identifiant du
 * défi, de sorte que deux défis portant le même code aient deux empreintes
 * différentes. `sent_to_masked` garde de quoi dire « code envoyé au
 * 06 •• •• •• 12 » sans conserver le numéro complet une seconde fois.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('otp_challenges', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('narrator_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignUuid('family_member_id')->nullable()->constrained()->cascadeOnDelete();

            $table->string('purpose', 32);
            $table->char('code_hash', 64);
            $table->string('channel', 16);
            $table->string('sent_to_masked', 64);

            $table->smallInteger('attempts')->default(0);
            $table->timestampTz('expires_at');
            $table->timestampTz('verified_at')->nullable();
            $table->timestampTz('locked_until')->nullable();

            $table->timestampTz('created_at')->nullable();

            $table->index(['narrator_id', 'purpose']);
            $table->index(['family_member_id', 'purpose']);
        });

        EnumCheck::add('otp_challenges', 'purpose', EnumCheck::of(OtpPurpose::class));
        EnumCheck::add('otp_challenges', 'channel', Channel::outboundValues());

        // Un défi appartient à un narrateur ou à un proche, jamais aux deux ni
        // à personne : sans sujet, il n'y a personne à qui envoyer le code.
        DB::statement('alter table otp_challenges add constraint otp_challenges_one_subject_check check ((narrator_id is not null) <> (family_member_id is not null))');
    }

    public function down(): void
    {
        Schema::dropIfExists('otp_challenges');
    }
};
