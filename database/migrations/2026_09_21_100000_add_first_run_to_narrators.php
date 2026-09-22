<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le tour de chauffe a été proposé une fois (T-247).
 *
 * Il se décidait sur « cette personne n'a jamais enregistré d'histoire », ce
 * qui n'est pas la même question : quelqu'un qui joue les quinze secondes
 * puis referme sans répondre n'a rien enregistré, et retrouvait le tutoriel à
 * l'ouverture suivante. À chaque fois, en somme, tant qu'un récit n'était pas
 * allé au bout — exactement le genre de répétition qui fait passer le produit
 * pour une machine qui ne reconnaît personne.
 *
 * La date est portée par la narratrice et non par le navigateur : un témoin
 * local se perd en navigation privée, sur un second téléphone, ou au premier
 * nettoyage — c'est le raisonnement déjà tenu pour `firstTime`, et il vaut
 * autant pour la mémoire du tour de chauffe.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('narrators', function (Blueprint $table): void {
            $table->timestampTz('first_run_at')->nullable()->after('opted_in_at');
        });
    }

    public function down(): void
    {
        Schema::table('narrators', function (Blueprint $table): void {
            $table->dropColumn('first_run_at');
        });
    }
};
