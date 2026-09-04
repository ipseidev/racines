<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * À quel point la narratrice est à l'aise avec un téléphone (T-136).
 *
 * Déclaré par l'acheteur à la commande. Sert à recommander l'option téléphone
 * et, plus tard, à doser l'aide sur les pages de la narratrice. Nul pour les
 * projets antérieurs et pour qui raconte sa propre histoire.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('narrators', function (Blueprint $table): void {
            $table->string('tech_comfort', 20)->nullable()->after('preferred_channel');
        });
    }

    public function down(): void
    {
        Schema::table('narrators', function (Blueprint $table): void {
            $table->dropColumn('tech_comfort');
        });
    }
};
