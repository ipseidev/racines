<?php

declare(strict_types=1);

use App\Enums\TranscriptKind;
use App\Support\Database\EnumCheck;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Les textes d'une histoire : le verbatim, le rendu Fluide, les corrections.
 *
 * **Le verbatim ne se supprime jamais** hors histoire supprimée : c'est la
 * parole de la personne, et le dossier exige qu'elle reste à côté du texte mis
 * au propre (doc 04 §1, « l'IA range, elle n'invente pas »). Une règle
 * Postgres l'impose au-delà du code — un `DELETE` direct échoue.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transcripts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('story_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('recording_id')->nullable()->constrained()->nullOnDelete();

            $table->string('kind', 16);
            $table->uuid('source_transcript_id')->nullable();
            $table->integer('version')->default(1);

            $table->string('provider', 32)->nullable();
            $table->string('provider_job_id')->nullable();
            $table->string('language', 8)->default('fr');

            $table->text('text');
            // Mots horodatés : ce qui permettra de suivre le texte pendant
            // l'écoute, et de retrouver un passage dans l'audio (bloc 08).
            $table->jsonb('words')->nullable();
            $table->jsonb('metadata')->nullable();

            $table->string('edited_by_type')->nullable();
            $table->string('edited_by_id', 64)->nullable();

            $table->boolean('is_current')->default(true);
            $table->timestampTz('created_at')->nullable();

            $table->index(['story_id', 'kind']);
        });

        Schema::table('transcripts', function (Blueprint $table): void {
            $table->foreign('source_transcript_id')->references('id')->on('transcripts')->nullOnDelete();
        });

        EnumCheck::add('transcripts', 'kind', EnumCheck::of(TranscriptKind::class));

        // Un seul texte courant par nature et par histoire.
        DB::statement('create unique index transcripts_one_current_per_kind on transcripts (story_id, kind) where is_current');

        DB::unprepared(<<<'SQL'
            create or replace function forbid_verbatim_delete() returns trigger as $$
            begin
                if old.kind = 'verbatim'
                   and (select state from stories where id = old.story_id) <> 'deleted' then
                    raise exception 'a verbatim transcript is never deleted while its story lives (story=%)', old.story_id;
                end if;

                return old;
            end;
            $$ language plpgsql;

            create trigger transcripts_verbatim_no_delete
                before delete on transcripts
                for each row execute function forbid_verbatim_delete();
        SQL);
    }

    public function down(): void
    {
        DB::unprepared('drop trigger if exists transcripts_verbatim_no_delete on transcripts');
        DB::unprepared('drop function if exists forbid_verbatim_delete()');

        Schema::dropIfExists('transcripts');
    }
};
