<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\User;
use Filament\Facades\Filament;

/**
 * La double authentification du back-office.
 *
 * Le doc 04 §12 la veut **obligatoire**, et pas « recommandée ». La raison est
 * dans ce que le back-office donne : la voix et les récits intimes de familles
 * entières. Un mot de passe support qui fuit, c'est la fuite de tout.
 *
 * Obligatoire signifie ici : tant qu'elle n'est pas configurée, aucune page du
 * panneau ne s'ouvre — pas même le tableau de bord. On ne compte pas sur la
 * bonne volonté de quelqu'un qui est pressé.
 */
it('envoie un membre du personnel configurer sa double authentification', function (UserRole $role): void {
    $user = User::factory()->create(['role' => $role]);

    $this->actingAs($user)
        ->get('/admin')
        ->assertRedirectContains('multi-factor');
})->with([
    'administrateur' => UserRole::Admin,
    'support' => UserRole::Support,
    'support en lecture' => UserRole::SupportReadonly,
]);

it('ouvre le panneau une fois la double authentification configurée', function (): void {
    $user = User::factory()->admin()->withAppAuthentication()->create();

    $this->actingAs($user)->get('/admin')->assertOk();
});

it('refuse le panneau à une Initiateur·rice, même avec la double authentification', function (): void {
    // L'ordre compte : on ne veut pas qu'un client se retrouve à configurer
    // une application d'authentification pour un panneau qu'il n'ouvrira
    // jamais. La permission passe avant.
    $user = User::factory()->withAppAuthentication()->create(['role' => UserRole::Initiator]);

    $this->actingAs($user)->get('/admin')->assertForbidden();
});

it('déclare le fournisseur d’application comme seul facteur', function (): void {
    $providers = Filament::getPanel('admin')->getMultiFactorAuthenticationProviders();

    // Une application TOTP, et des codes de récupération. Pas de SMS : un
    // second facteur qui passe par le réseau téléphonique n'en est pas un.
    expect(array_keys($providers))->toBe(['app']);
});

it('exige la double authentification sur le panneau', function (): void {
    expect(Filament::getPanel('admin')->isMultiFactorAuthenticationRequired())->toBeTrue();
});

it('range le secret sur les mêmes colonnes que Fortify', function (): void {
    $user = User::factory()->admin()->withAppAuthentication()->create();

    // Un seul secret, un seul endroit. Deux magasins de double
    // authentification finiraient par diverger, et le jour où ils divergent
    // quelqu'un reste dehors avec un code valide en main.
    expect($user->getAppAuthenticationSecret())->not->toBeNull()
        ->and($user->two_factor_secret)->not->toBeNull()
        ->and($user->getAppAuthenticationRecoveryCodes())->not->toBeEmpty();
});

it('nomme le titulaire par son courriel dans l’application d’authentification', function (): void {
    $user = User::factory()->admin()->withAppAuthentication()->create(['email' => 'sophie@exemple.test']);

    // C'est ce qui s'affiche dans l'application : un « admin » anonyme au
    // milieu de douze comptes ne se retrouve pas.
    expect($user->getAppAuthenticationHolderName())->toBe('sophie@exemple.test');
});
