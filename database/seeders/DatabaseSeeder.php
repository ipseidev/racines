<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Les événements de modèle ne sont pas coupés ici, contrairement à l'habitude
 * du squelette Laravel : c'est un événement `saved` qui traduit `users.role`
 * en rôle de back-office. Les couper laissait le compte d'administration semé
 * sans aucune permission, donc à la porte du panneau.
 */
final class DatabaseSeeder extends Seeder
{
    /**
     * Données de référence partout ; comptes et projet de démonstration hors
     * production.
     */
    public function run(): void
    {
        $this->call(ReferenceDataSeeder::class);

        if (! app()->isProduction()) {
            $this->call([
                AdminUserSeeder::class,
                DemoProjectSeeder::class,
                E2ELinksSeeder::class,
            ]);
        }
    }
}
