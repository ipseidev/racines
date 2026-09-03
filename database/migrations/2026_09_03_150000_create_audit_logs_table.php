<?php

declare(strict_types=1);

use App\Audit\AuditLog;
use App\Enums\ActorContext;
use App\Support\Database\EnumCheck;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Le journal d'audit, et ce qui le rend inviolable.
 *
 * Le dossier 04 §12 exige une journalisation « inviolable de toutes les
 * actions support, lecture comprise ». Un paquet générique n'y suffit pas :
 * ils journalisent les écritures, et laissent les `UPDATE` passer. Ici le
 * trigger refuse toute modification et toute suppression, et chaque ligne
 * porte l'empreinte de la précédente — effacer une trace demanderait de
 * recalculer tout ce qui suit, ce que `audit:verify` verrait.
 *
 * `audit_chain_head` est une table d'une seule ligne, et son unique raison
 * d'être est le verrou : `previous_hash` se lit sur la dernière ligne écrite,
 * et deux transactions concurrentes liraient la même. Verrouiller la dernière
 * ligne d'une table en append-only ne dit rien à celui qui insère juste
 * après ; verrouiller une ligne dédiée, si.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();

            $table->timestampTz('occurred_at');

            // Nullable : une purge programmée n'a pas d'auteur humain.
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_role', 32);
            $table->string('actor_context', 24);

            $table->string('action');
            $table->string('subject_type')->nullable();
            $table->string('subject_id')->nullable();
            $table->foreignUuid('project_id')->nullable()->constrained()->nullOnDelete();

            $table->char('ip_hash', 64)->nullable();
            $table->jsonb('payload')->nullable();

            $table->char('previous_hash', 64);
            $table->char('hash', 64);

            // Les deux lectures réelles : « qui a touché à cette famille ? »
            // et « qu'a fait cette personne ? ».
            $table->index(['project_id', 'occurred_at']);
            $table->index(['actor_user_id', 'occurred_at']);
            $table->index(['subject_type', 'subject_id']);
        });

        EnumCheck::add('audit_logs', 'actor_context', EnumCheck::of(ActorContext::class));

        Schema::create('audit_chain_head', function (Blueprint $table): void {
            $table->smallInteger('id')->primary();
            $table->char('hash', 64);
        });

        DB::table('audit_chain_head')->insert(['id' => 1, 'hash' => AuditLog::GENESIS]);

        // Une seule ligne, pour toujours : la tête de chaîne est un point de
        // rendez-vous, pas une collection.
        DB::statement('alter table audit_chain_head add constraint audit_chain_head_single check (id = 1)');

        DB::unprepared(<<<'SQL'
            create or replace function audit_logs_append_only() returns trigger as $$
            begin
                raise exception 'audit_logs est en écriture seule : % refusé sur la ligne %',
                    tg_op, coalesce(old.id, -1);
            end;
            $$ language plpgsql;

            create trigger audit_logs_append_only
                before update or delete on audit_logs
                for each row execute function audit_logs_append_only();
            SQL);
    }

    public function down(): void
    {
        DB::unprepared('drop trigger if exists audit_logs_append_only on audit_logs');
        DB::unprepared('drop function if exists audit_logs_append_only()');

        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('audit_chain_head');
    }
};
