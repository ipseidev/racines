<?php

declare(strict_types=1);

use App\Enums\ConsentKind;
use App\Support\Database\EnumCheck;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le mandat : un proche autorisé à valider à la place du narrateur.
 *
 * Une exception au principe « le narrateur est souverain », et la table le
 * dit dans sa forme. `consent_id` n'est pas nullable : un mandat sans
 * consentement journalisé du narrateur n'existe pas. `scope` est une liste
 * fermée d'actes, jamais un blanc-seing — aujourd'hui `["validate"]`, et
 * rien d'autre.
 *
 * `revoked_at` plutôt qu'une suppression : savoir qu'un mandat a existé,
 * qui le détenait et quand il a cessé fait partie de l'audit (bloc 11).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Un nouveau motif de consentement : la contrainte de la base doit
        // l'accepter, sinon un mandat serait impossible à consentir.
        EnumCheck::drop('consents', 'kind');
        EnumCheck::add('consents', 'kind', EnumCheck::of(ConsentKind::class));

        Schema::create('mandates', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('project_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('narrator_id')->constrained()->cascadeOnDelete();

            // Un compte ou un proche : les deux peuvent tenir un mandat.
            $table->string('holder_type');
            $table->string('holder_id', 64);

            $table->jsonb('scope');
            $table->foreignUuid('consent_id')->constrained()->restrictOnDelete();

            $table->timestampTz('granted_at');
            $table->timestampTz('revoked_at')->nullable();
            $table->timestampsTz();

            $table->index(['narrator_id', 'revoked_at']);
            $table->index(['holder_type', 'holder_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mandates');

        EnumCheck::drop('consents', 'kind');
        EnumCheck::add('consents', 'kind', [
            'voice_recording',
            'transcription',
            'ai_rendering',
            'family_sharing',
            'sensitive_categories',
            'phone_call_recording',
            'photo_rights',
            'post_mortem_directives',
        ]);
    }
};
