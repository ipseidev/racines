<?php

declare(strict_types=1);

use App\Models\User;
use Inertia\Testing\AssertableInertia;

/**
 * Les pages de compte parlent français (T-135).
 *
 * Le kit de démarrage les livrait en anglais, en dur. Elles ont désormais leur
 * espace de langue, servi par le nom de route comme les autres espaces : la
 * page de connexion est la première chose que voit quelqu'un qui revient.
 */
it('sert le catalogue des pages de compte à la connexion et à l’inscription', function (): void {
    $this->get('/login')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('auth/login')
            ->has('i18n.auth.pages.login.title')
            ->has('i18n.auth.fields.email')
            ->has('i18n.common'),
        );

    $this->get('/register')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('auth/register')
            ->has('i18n.auth.pages.register.title'),
        );
});

it('sert le catalogue sur les pages de mot de passe et de vérification', function (): void {
    $this->get('/forgot-password')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('auth/forgot-password')
            ->has('i18n.auth.pages.forgot_password.title'),
        );

    $this->actingAs(User::factory()->unverified()->create())
        ->get('/email/verify')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('auth/verify-email')
            ->has('i18n.auth.pages.verify_email.title'),
        );
});

it('ne charge pas le catalogue des pages de compte sur l’accueil', function (): void {
    // Chaque espace ne voyage qu'avec son fichier : la page d'accueil n'a pas
    // besoin des libellés de connexion.
    $this->get('/')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->missing('i18n.auth'),
        );
});
