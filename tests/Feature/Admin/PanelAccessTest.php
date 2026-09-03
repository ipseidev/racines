<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\User;

it('redirige un visiteur anonyme vers la connexion du panneau', function (): void {
    $this->get('/admin')->assertRedirect('/admin/login');
});

it('refuse le panneau à une Initiateur·rice', function (): void {
    $this->actingAs(User::factory()->create(['role' => UserRole::Initiator]))
        ->get('/admin')
        ->assertForbidden();
});

it('ouvre le panneau aux trois rôles du personnel', function (UserRole $role): void {
    // `withAppAuthentication` : depuis le bloc 11, la double authentification
    // est obligatoire, et un compte sans second facteur est renvoyé vers sa
    // configuration avant toute page. `MfaTest` éprouve ce renvoi ; ici on
    // vérifie la permission d'accès, une fois le facteur en place.
    $this->actingAs(User::factory()->withAppAuthentication()->create(['role' => $role]))
        ->get('/admin')
        ->assertOk();
})->with([
    'administrateur' => UserRole::Admin,
    'support' => UserRole::Support,
    'support en lecture' => UserRole::SupportReadonly,
]);

it('expose isStaff sur le modèle', function (): void {
    expect(User::factory()->create(['role' => UserRole::Support])->isStaff())->toBeTrue()
        ->and(User::factory()->create()->isStaff())->toBeFalse();
});

it('crée les utilisateurs en Initiateur·rice par défaut', function (): void {
    expect(User::factory()->create()->role)->toBe(UserRole::Initiator);
});
