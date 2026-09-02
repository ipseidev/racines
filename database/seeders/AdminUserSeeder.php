<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

/**
 * Compte d'administration pour le développement et les tests.
 *
 * Jamais exécuté en production : les comptes du personnel y sont créés à la
 * main, avec double authentification obligatoire (doc 04 §12).
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
    }
}
