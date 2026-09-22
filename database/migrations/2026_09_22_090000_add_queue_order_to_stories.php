<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le rang d'une question écrite par la famille dans la file d'envoi (T-255).
 *
 * Une question personnalisée devient une histoire PROPOSÉE dès qu'on l'écrit,
 * là où une question du corpus attend son tour dans
 * `project_question_settings.custom_order`. Deux natures, deux échelles — et
 * donc aucun moyen de faire passer l'une devant l'autre : les questions de la
 * famille passaient **toutes** devant, sans recours.
 *
 * `queue_order` place l'histoire proposée dans **la même échelle** que
 * `custom_order`. L'envoi compare alors deux entiers et n'a plus à connaître
 * la nature de ce qu'il compare.
 *
 * Nul pour tout ce qui n'attend pas dans la file : une histoire déjà
 * enregistrée n'a pas de rang à venir.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stories', function (Blueprint $table): void {
            $table->integer('queue_order')->nullable()->after('sequence');
        });
    }

    public function down(): void
    {
        Schema::table('stories', function (Blueprint $table): void {
            $table->dropColumn('queue_order');
        });
    }
};
