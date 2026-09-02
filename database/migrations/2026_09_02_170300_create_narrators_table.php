<?php

declare(strict_types=1);

use App\Enums\Channel;
use App\Support\Database\EnumCheck;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Le narrateur n'a pas de compte : il agit par jeton (glossaire §1). Plusieurs
 * narrateurs sont possibles en base, un seul est principal, et le multi-
 * narrateurs reste hors interface au MVP (PRD §2).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('narrators', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('project_id')->constrained()->cascadeOnDelete();

            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('display_name');

            $table->string('email')->nullable();
            $table->string('phone_e164', 20)->nullable();
            $table->string('preferred_channel', 16)->default(Channel::Sms->value);

            $table->boolean('is_primary')->default(false);
            $table->smallInteger('birth_year')->nullable();

            $table->timestampTz('opted_in_at')->nullable();
            $table->timestampTz('opted_out_at')->nullable();
            // Purge des coordonnées après le service (doc 04 §5).
            $table->timestampTz('contact_deletion_due_at')->nullable();

            $table->timestampsTz();
        });

        // Un seul narrateur principal par projet, garanti par l'index partiel.
        DB::statement('create unique index narrators_one_primary on narrators (project_id) where is_primary');

        // Sans courriel ni téléphone, aucun lien ne peut être envoyé.
        DB::statement('alter table narrators add constraint narrators_reachable_check check (email is not null or phone_e164 is not null)');

        // Le téléphone n'est jamais un canal d'envoi automatique (R-9).
        EnumCheck::add('narrators', 'preferred_channel', Channel::outboundValues());
    }

    public function down(): void
    {
        Schema::dropIfExists('narrators');
    }
};
