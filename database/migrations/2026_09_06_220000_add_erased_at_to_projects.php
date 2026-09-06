<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La date d'effacement d'un projet (bloc 14, doc 04 §4).
 *
 * La ligne du projet survit à l'effacement : les commandes y pointent, le
 * journal d'audit s'y rattache, et la supprimer casserait la structure dont
 * la preuve a besoin. Ce qui disparaît, c'est **le contenu** — voix, textes,
 * identités.
 *
 * D'où cette colonne : sans elle, un projet effacé ressemble à un projet
 * vide, et rien ne distingue « cette famille a demandé l'effacement » de
 * « cette famille n'a jamais rien enregistré ». La différence compte le jour
 * où quelqu'un réclame ses données.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            $table->timestamp('erased_at')->nullable()->after('hosting_ends_at');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            $table->dropColumn('erased_at');
        });
    }
};
