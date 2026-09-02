<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * L'immuabilité de l'audio source cède devant la suppression demandée.
 *
 * Deux principes du dossier se rencontraient ici : « l'audio source est
 * sacré, jamais remplacé, jamais supprimé **hors état `deleted`** » et le
 * droit du narrateur à faire effacer son récit. Le déclencheur du bloc 04
 * lisait le premier sans sa réserve : il refusait aussi l'effacement, et
 * `PurgeDeletedStory` échouait en base.
 *
 * La règle complète : `original_path` ne peut pas **changer** de valeur, et
 * ne peut être mis à `null` que si l'histoire est `deleted`. L'immuabilité
 * protège contre l'écrasement, pas contre l'effacement consenti.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            create or replace function recordings_original_immutable() returns trigger as $$
            begin
                if old.confirmed_at is null or old.original_path is null then
                    return new;
                end if;

                if new.original_path is not distinct from old.original_path then
                    return new;
                end if;

                -- Effacement : autorisé, et seulement pour une histoire morte.
                if new.original_path is null
                   and (select state from stories where id = old.story_id) = 'deleted' then
                    return new;
                end if;

                raise exception 'recordings.original_path is immutable once confirmed (id=%)', old.id;
            end;
            $$ language plpgsql;
        SQL);
    }

    public function down(): void
    {
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
        SQL);
    }
};
