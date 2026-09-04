<?php

declare(strict_types=1);

use App\Models\CheckoutDraft;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

/**
 * Le compte se crée dans le tunnel, sans le quitter (T-135).
 *
 * L'étape 4 pose l'adresse de retour avant d'afficher les formulaires : quand
 * Fortify a créé le compte ou ouvert la session, il renvoie à l'étape 5 et non
 * à l'espace. Quelqu'un qui vient d'écrire le prénom de sa mère et un mot
 * personnel ne doit pas se retrouver sur un tableau de bord vide.
 */
it('pose l’adresse de retour vers l’étape 5 quand on arrive à l’étape du compte sans compte', function (): void {
    $this->get('/acheter?step=4')
        ->assertOk()
        ->assertSessionHas('url.intended', url('/acheter?step=5'));
});

it('ne pose rien quand on est déjà connecté', function (): void {
    $this->actingAs(User::factory()->create())
        ->get('/acheter?step=4')
        ->assertOk()
        ->assertSessionMissing('url.intended');
});

it('ne pose rien aux autres étapes', function (): void {
    $this->get('/acheter?step=2')
        ->assertOk()
        ->assertSessionMissing('url.intended');
});

it('revient à l’étape 5 après la création du compte', function (): void {
    $this->withSession(['url.intended' => url('/acheter?step=5')])
        ->post('/register', [
            'name' => 'Camille Martin',
            'email' => 'camille@example.test',
            'password' => 'un-mot-de-passe-solide-12',
            'password_confirmation' => 'un-mot-de-passe-solide-12',
        ])
        ->assertRedirect('/acheter?step=5');

    $this->assertAuthenticated();
});

it('revient à l’étape 5 après la connexion', function (): void {
    $user = User::factory()->create(['password' => 'un-mot-de-passe-solide-12']);

    $this->withSession(['url.intended' => url('/acheter?step=5')])
        ->post('/login', [
            'email' => $user->email,
            'password' => 'un-mot-de-passe-solide-12',
        ])
        ->assertRedirect('/acheter?step=5');
});

it('donne à la page de merci le prénom et la date de l’invitation', function (): void {
    $draft = new CheckoutDraft([
        'step' => 6,
        'payload' => [
            'for' => 'relative',
            'narrator_first_name' => 'Jeanne',
            'gift_send_at' => '2026-10-03',
        ],
        'expires_at' => now()->addDays(7),
    ]);
    $draft->save();

    $this->withCookie('checkout_draft', $draft->id)
        ->get('/acheter/merci?session_id=cs_test_1')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('public/CheckoutThanks')
            ->where('narratorFirstName', 'Jeanne')
            ->where('giftSendAt', '2026-10-03'),
        );
});

it('reste polie sans brouillon', function (): void {
    // Le brouillon peut avoir disparu : cookie expiré, autre navigateur. La
    // page dit merci sans prénom, et surtout ne crée pas un brouillon pour rien.
    $this->get('/acheter/merci')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('public/CheckoutThanks')
            ->where('narratorFirstName', null)
            ->where('giftSendAt', null),
        );

    expect(CheckoutDraft::query()->count())->toBe(0);
});
