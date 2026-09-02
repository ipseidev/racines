<?php

declare(strict_types=1);

use App\Support\Database\EnumCheck;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * L'audio source, jamais remplacé, jamais supprimé hors état `deleted`.
 *
 * C'est le principe non négociable de la roadmap : « l'audio source est
 * sacré ». Un déclencheur Postgres l'impose au-delà du code — une fois
 * `confirmed_at` posé, `original_path` ne peut plus changer. Réenregistrer
 * crée un nouveau `Recording` et laisse l'ancien en place, `is_current` à faux.
 */
return new class extends Migration
{
    /** @var list<string> */
    private array $sources = ['browser', 'phone_operator', 'upload_admin'];

    /** @var list<string> */
    private array $statuses = ['initiated', 'uploading', 'completed', 'failed', 'aborted'];

    public function up(): void
    {
        Schema::create('recordings', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('story_id')->constrained()->cascadeOnDelete();

            $table->string('source', 32)->default('browser');

            $table->string('original_disk', 32)->default('r2');
            $table->string('original_path')->nullable();
            $table->string('original_mime', 64)->nullable();
            $table->bigInteger('original_bytes')->nullable();

            $table->decimal('duration_seconds', 8, 2)->nullable();
            $table->string('derived_mp3_path')->nullable();

            $table->string('replica_path')->nullable();
            $table->timestampTz('replicated_at')->nullable();

            $table->string('upload_id')->nullable();
            $table->string('upload_status', 16)->default('initiated');
            $table->timestampTz('confirmed_at')->nullable();
            $table->char('checksum_sha256', 64)->nullable();

            $table->boolean('is_current')->default(true);

            // Un enregistrement interrompu par un appel produit plusieurs
            // segments : on garde la liste pour pouvoir les concaténer, et
            // pour savoir, après coup, qu'il y a eu une interruption.
            $table->jsonb('segments')->nullable();

            $table->jsonb('device_info')->nullable();

            $table->timestampsTz();

            $table->index('story_id');
        });

        EnumCheck::add('recordings', 'source', $this->sources);
        EnumCheck::add('recordings', 'upload_status', $this->statuses);

        // Un seul enregistrement courant par histoire.
        DB::statement('create unique index recordings_one_current on recordings (story_id) where is_current');

        // Un original qui existe ne bouge plus jamais. Le déclencheur refuse
        // l'écriture quel que soit le chemin de code qui la tente.
        //
        // Il laisse en revanche *renseigner* `original_path` une première fois
        // après confirmation : un enregistrement interrompu est confirmé sur
        // ses segments — c'est eux qui sont en sécurité — et son fichier
        // recollé n'arrive qu'ensuite, par `ConcatenateSegments`.
        DB::unprepared(<<<'SQL'
            create or replace function recordings_original_immutable() returns trigger as $$
            begin
                if old.confirmed_at is not null
                   and old.original_path is not null
                   and new.original_path is distinct from old.original_path then
                    raise exception 'recordings.original_path is immutable once confirmed (id=%)', old.id;
                end if;

                return new;
            end;
            $$ language plpgsql;

            create trigger recordings_original_immutable
                before update on recordings
                for each row execute function recordings_original_immutable();
        SQL);
    }

    public function down(): void
    {
        DB::unprepared('drop trigger if exists recordings_original_immutable on recordings');
        DB::unprepared('drop function if exists recordings_original_immutable()');

        Schema::dropIfExists('recordings');
    }
};
