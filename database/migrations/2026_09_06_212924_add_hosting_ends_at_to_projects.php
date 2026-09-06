<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La fin d'hébergement d'un projet (doc 04 §7, R-10).
 *
 * Le dossier interdit « pour toujours » (R-11) et impose en échange une durée
 * **annoncée**. La colonne la porte projet par projet plutôt que de la
 * recalculer depuis un réglage : baisser la durée d'hébergement un jour ne
 * doit pas raccourcir l'engagement pris envers les familles déjà servies.
 *
 * Soixante jours avant l'échéance, un export part sans qu'on le demande. Une
 * fin d'hébergement qui surprendrait une famille serait la trahison de la
 * promesse, même en l'ayant écrite quelque part.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            $table->timestamp('hosting_ends_at')->nullable()->after('finalization_ends_at');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            $table->dropColumn('hosting_ends_at');
        });
    }
};
