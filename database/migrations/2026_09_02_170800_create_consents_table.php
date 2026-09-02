<?php

declare(strict_types=1);

use App\Enums\ConsentChannel;
use App\Enums\ConsentKind;
use App\Enums\ConsentStatus;
use App\Support\Database\EnumCheck;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Un consentement par ligne, jamais modifié : une révocation ajoute une ligne
 * `revoked` (doc 04 §2).
 *
 * `subject_id` est une chaîne et non un entier ou un uuid : le sujet peut être
 * un narrateur ou un proche (uuid) comme un utilisateur (identifiant
 * séquentiel). C'est le prix de la relation polymorphe voulue par l'annexe B.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consents', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('project_id')->constrained()->cascadeOnDelete();

            $table->string('subject_type');
            $table->string('subject_id', 64);

            $table->string('kind', 32);
            $table->string('status', 16);
            $table->string('channel', 16);
            $table->string('text_version', 16);

            $table->char('ip_hash', 64)->nullable();
            $table->string('user_agent')->nullable();

            $table->timestampTz('granted_at');
            $table->timestampTz('revoked_at')->nullable();

            // Renseigné quand le consentement est recueilli par un opérateur.
            $table->foreignId('recorded_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestampTz('created_at')->nullable();

            $table->index(['subject_type', 'subject_id', 'kind']);
            $table->index(['project_id', 'kind']);
        });

        EnumCheck::add('consents', 'kind', EnumCheck::of(ConsentKind::class));
        EnumCheck::add('consents', 'status', EnumCheck::of(ConsentStatus::class));
        EnumCheck::add('consents', 'channel', EnumCheck::of(ConsentChannel::class));

        // Un consentement recueilli par téléphone est toujours attribué à un
        // opérateur nommé : c'est ce qui rend l'accord oral vérifiable (D-9).
        DB::statement("alter table consents add constraint consents_phone_operator_check check (channel <> 'phone' or recorded_by_user_id is not null)");
    }

    public function down(): void
    {
        Schema::dropIfExists('consents');
    }
};
