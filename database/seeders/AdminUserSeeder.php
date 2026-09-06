<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

/**
 * Comptes du back-office pour le développement et les tests.
 *
 * Jamais exécuté en production : les comptes du personnel y sont créés à la
 * main, avec double authentification obligatoire (doc 04 §12).
 *
 * **Deux comptes et non un.** Le point 4 du checkpoint du bloc 11 vérifie
 * qu'un compte en lecture seule ne voit aucun bouton d'action et reçoit un 403
 * s'il force le passage — un bouton n'est pas une autorisation, et le serveur
 * ne le croit pas sur parole. La feuille demandait ce geste sans que le décor
 * fournisse de quoi le faire (T-173).
 */
final class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('Ce seeder ne doit pas tourner en production.');
        }

        $email = (string) config('product.seeding.admin_email');
        $password = (string) config('product.seeding.admin_password');

        User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Administration',
                'password' => Hash::make($password),
                'email_verified_at' => now(),
                'role' => UserRole::Admin,
            ],
        );

        // Un second compte d'administration, **sans second facteur**.
        //
        // Le point 1 du checkpoint du bloc 11 demande de voir la configuration
        // du second facteur forcée au premier accès. Or `E2ELinksSeeder` la
        // configure d'avance sur le compte principal, pour que la suite bout en
        // bout puisse se connecter : l'écran demande alors un code au lieu de
        // la proposer, et le point devient injouable (T-180).
        User::query()->updateOrCreate(
            ['email' => 'premiere-connexion@example.test'],
            [
                'name' => 'Administration, première connexion',
                'password' => Hash::make($password),
                'email_verified_at' => now(),
                'role' => UserRole::Admin,
            ],
        );

        // Même mot de passe : c'est un décor local, et deux mots de passe à
        // retenir feraient chercher dans un fichier au milieu d'une
        // vérification.
        User::query()->updateOrCreate(
            ['email' => 'lecture@example.test'],
            [
                'name' => 'Support, lecture seule',
                'password' => Hash::make($password),
                'email_verified_at' => now(),
                'role' => UserRole::SupportReadonly,
            ],
        );
    }
}
