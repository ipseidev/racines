<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Un proche peut proposer une question (R-1, dossier v3.1).
 *
 * Deux changements qui n'en font qu'un. Le droit d'abord : `can_contribute`
 * n'ouvrait que le dépôt d'une photo sur une histoire déjà racontée — une
 * décoration. Il devient `can_ask`, le droit de **poser une question**, avec
 * ses photos jointes à elle. C'est la même intention, portée au bon endroit :
 * une photo qui appelle un récit vaut mieux qu'une photo collée dessus.
 *
 * L'auteur ensuite. `stories` n'avait aucune colonne d'auteur : la frise du
 * tableau de bord ne pouvait pas dire qui avait posé la question, et pour
 * cause — personne d'autre que l'Initiateur·rice ne le pouvait. Nulle, la
 * colonne signifie « elle » ; renseignée, elle nomme le proche.
 *
 * `nullOnDelete` et non `cascade` : un proche retiré du cercle ne fait pas
 * disparaître la question qu'il a posée, ni l'histoire qu'elle a produite. Ce
 * qu'une famille a raconté ne dépend pas de qui reste dans la liste.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('family_members', function (Blueprint $table): void {
            $table->renameColumn('can_contribute', 'can_ask');
        });

        Schema::table('stories', function (Blueprint $table): void {
            $table->foreignUuid('proposed_by_family_member_id')
                ->nullable()
                ->after('custom_question_text')
                ->constrained('family_members')
                ->nullOnDelete();
        });

        /*
         * Une question proposée porte forcément son texte : sans lui, elle
         * vient du corpus, et le corpus n'appartient à personne. La règle est
         * dans la base plutôt que dans une action, parce qu'elle vaut aussi
         * pour un import, une correction au support ou une console.
         */
        DB::statement('alter table stories add constraint stories_proposed_by_has_text_check check (proposed_by_family_member_id is null or custom_question_text is not null)');
    }

    public function down(): void
    {
        DB::statement('alter table stories drop constraint if exists stories_proposed_by_has_text_check');

        Schema::table('stories', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('proposed_by_family_member_id');
        });

        Schema::table('family_members', function (Blueprint $table): void {
            $table->renameColumn('can_ask', 'can_contribute');
        });
    }
};
