<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Filament\Pages\ManageBrand;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;

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

it('envoie le compte semé configurer sa double authentification', function (): void {
    /*
     * `AdminUserSeeder` seul, et non `DatabaseSeeder` : ce dernier enchaîne
     * `E2ELinksSeeder`, qui pose un second facteur à valeur connue pour la
     * suite bout en bout. C'est justement ce qu'on ne veut **pas** ici — on
     * éprouve le compte tel qu'il naît en production, sans second facteur, et
     * donc renvoyé vers sa configuration à la première connexion. Un secret
     * TOTP dans le seeder de production serait un secret partagé, connu de
     * quiconque lit le dépôt.
     */
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(AdminUserSeeder::class);

    $admin = User::query()->where('email', config('product.seeding.admin_email'))->sole();

    expect($admin->getAppAuthenticationSecret())->toBeNull();

    $this->actingAs($admin)->get('/admin')->assertRedirectContains('multi-factor');
});

it('ouvre le back-office au compte semé une fois son second facteur posé', function (): void {
    $this->seed(DatabaseSeeder::class);

    $admin = User::query()->where('email', config('product.seeding.admin_email'))->sole();

    // Le décor complet lui en donne un : la suite bout en bout doit pouvoir
    // franchir la double authentification sans la désactiver.
    expect($admin->getAppAuthenticationSecret())->not->toBeNull();

    $this->actingAs($admin)->get('/admin')->assertOk();
    $this->actingAs($admin)->get(ManageBrand::getUrl())->assertOk();
});
