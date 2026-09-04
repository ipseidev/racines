<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * L'option téléphone s'ouvre par défaut (T-137).
 *
 * Pennant mémorise la première résolution d'un drapeau. Les environnements
 * déjà migrés gardaient donc « fermé » ; on efface cette valeur pour que le
 * nouveau défaut s'applique. Le plafond, lui, reste calculé.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('features')) {
            DB::table('features')->where('name', 'phone-option-offer')->delete();
        }
    }

    public function down(): void
    {
        // Rien à défaire : la valeur sera résolue de nouveau à la demande.
    }
};
