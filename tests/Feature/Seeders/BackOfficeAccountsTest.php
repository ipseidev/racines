<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Le décor sème de quoi jouer le point 4 du bloc 11.
 *
 * Ce point vérifie qu'un compte en lecture seule ne voit aucun bouton d'action
 * et reçoit un 403 s'il force le passage — un bouton n'est pas une
 * autorisation. Encore faut-il un tel compte : le décor n'en semait aucun, et
 * la feuille des vérifications demandait le geste sans donner de quoi le
 * faire. Un checkpoint dont le décor manque n'est pas un checkpoint (T-173).
 */
it('sème un compte en lecture seule à côté de l’administration', function (): void {
    $this->seed(AdminUserSeeder::class);

    $lecteur = User::query()->where('role', UserRole::SupportReadonly)->first();

    expect($lecteur)->not->toBeNull()
        ->and($lecteur->hasRole(UserRole::SupportReadonly->value))->toBeTrue();
});

it('donne au lecteur strictement moins de droits qu’à l’administration', function (): void {
    $this->seed(AdminUserSeeder::class);

    $admin = User::query()->where('role', UserRole::Admin)->sole();
    $lecteur = User::query()->where('role', UserRole::SupportReadonly)->sole();

    expect($lecteur->getAllPermissions()->count())
        ->toBeLessThan($admin->getAllPermissions()->count());
});
