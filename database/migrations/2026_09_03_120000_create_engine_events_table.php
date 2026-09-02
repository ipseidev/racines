<?php

declare(strict_types=1);

use App\Enums\EngineOutcome;
use App\Enums\EngineRuleId;
use App\Support\Database\EnumCheck;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Chaque déclenchement du moteur, et ce qu'il a produit.
 *
 * C'est la table qui fait du moteur un **actif défendable** plutôt qu'une
 * collection de messages : sans `outcome`, on saurait combien on a relancé,
 * pas si ça a servi. Le dossier 01 en fait le différenciateur n°1, et un
 * différenciateur qu'on ne mesure pas n'en est pas un.
 *
 * `dedupe_key` est **unique**, et c'est le cœur du mécanisme : la ligne est
 * insérée **avant** l'envoi, dans la même transaction. Deux ticks
 * simultanés — un `withoutOverlapping` qui a lâché, une reprise de file —
 * ne peuvent donc pas envoyer deux fois le même message. La contrainte de
 * base fait le travail, pas une vérification en PHP.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('engine_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('project_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('story_id')->nullable()->constrained()->nullOnDelete();

            $table->string('rule_id', 48);
            $table->string('occurrence_key');
            // `rule_id:occurrence_key` — l'idempotence vit ici.
            $table->string('dedupe_key')->unique();

            $table->timestampTz('fired_at');
            $table->jsonb('action_taken')->nullable();

            $table->string('outcome', 16)->nullable();
            $table->timestampTz('outcome_at')->nullable();

            $table->timestampTz('created_at')->nullable();

            $table->index(['project_id', 'rule_id']);
            // Ce que lit `MeasureResumptions` chaque heure.
            $table->index(['outcome', 'fired_at']);
        });

        EnumCheck::add('engine_events', 'rule_id', EnumCheck::of(EngineRuleId::class));
        EnumCheck::add('engine_events', 'outcome', EnumCheck::of(EngineOutcome::class), nullable: true);
    }

    public function down(): void
    {
        Schema::dropIfExists('engine_events');
    }
};
