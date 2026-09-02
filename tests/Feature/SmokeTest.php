<?php

declare(strict_types=1);

use Inertia\Testing\AssertableInertia;

it('affiche la page d’accueil', function (): void {
    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('welcome'));
});

it('sert l’application sur le fuseau et la langue du produit', function (): void {
    expect(config('app.timezone'))->toBe('Europe/Paris')
        ->and(config('app.locale'))->toBe('fr')
        ->and(config('app.fallback_locale'))->toBe('fr');
});
