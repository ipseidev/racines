<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Données de référence, indispensables au fonctionnement de l'application :
 * rôles, permissions, textes de consentement. Semées dans tous les
 * environnements, tests compris. Rien de démonstratif ici.
 */
final class ReferenceDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            ConsentTextSeeder::class,
            QuestionSeeder::class,
        ]);
    }
}
