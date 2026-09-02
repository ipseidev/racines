<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Filament\Pages\ManageBrand;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;

/**
 * Régression : le compte d'administration semé n'obtenait aucun rôle de
 * back-office, parce que `DatabaseSeeder` coupait les événements de modèle et
 * que la traduction de `users.role` en permissions est un événement `saved`.
 * Le compte existait, avec le bon mot de passe, et restait à la porte.
 */
it('sème un compte d’administration qui peut réellement entrer dans le panneau', function (): void {
    $this->seed(DatabaseSeeder::class);

    $admin = User::query()->where('email', config('product.seeding.admin_email'))->sole();

    expect($admin->role)->toBe(UserRole::Admin)
        ->and($admin->hasRole(UserRole::Admin->value))->toBeTrue()
        ->and($admin->can('admin.access'))->toBeTrue()
        ->and($admin->can('brand.manage'))->toBeTrue()
        ->and($admin->canAccessPanel(Filament\Facades\Filament::getPanel('admin')))->toBeTrue();
});

it('ouvre le back-office au compte semé', function (): void {
    $this->seed(DatabaseSeeder::class);

    $admin = User::query()->where('email', config('product.seeding.admin_email'))->sole();

    $this->actingAs($admin)->get('/admin')->assertOk();
    $this->actingAs($admin)->get(ManageBrand::getUrl())->assertOk();
});
