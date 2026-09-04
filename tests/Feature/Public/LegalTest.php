<?php

declare(strict_types=1);

use App\Settings\PilotSettings;
use Inertia\Testing\AssertableInertia;

/**
 * Les pages légales.
 *
 * Rendues depuis des fichiers markdown, avec le nom de l'entité substitué
 * depuis les réglages : un texte juridique qui nomme la mauvaise société est
 * un texte inopposable, et le nom n'est pas encore arrêté.
 *
 * L'état de validation juridique est exposé aux pages ; il ne change pas de
 * lui-même, c'est un acte posé dans l'administration (T-145 : plus de bandeau).
 */
it('rend les trois pages légales', function (string $path, string $needle): void {
    $this->get($path)
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('public/Legal')
            ->has('title')
            ->where('html', fn (mixed $html) => is_string($html) && str_contains($html, $needle)),
        );
})->with([
    ['/cgv', 'rétractation'],
    ['/confidentialite', 'sous-traitance'],
    ['/mentions-legales', 'Éditeur'],
]);

it('substitue l’entité et l’adresse des réglages', function (): void {
    $this->get('/mentions-legales')->assertInertia(fn (AssertableInertia $page) => $page
        ->where('html', function (mixed $html): bool {
            expect(is_string($html))->toBeTrue();

            // Aucun gabarit non résolu ne doit atteindre l'écran.
            return ! str_contains((string) $html, '{{ legal_entity }}')
                && ! str_contains((string) $html, '{{ support_email }}');
        }),
    );
});

it('expose aux pages l’état de la validation juridique, posé dans l’administration', function (): void {
    $this->get('/cgv')->assertInertia(fn (AssertableInertia $page) => $page
        ->where('legalValidated', false),
    );

    app(PilotSettings::class)->fill(['legal_validated_at' => now()->toIso8601String()])->save();

    $this->get('/cgv')->assertInertia(fn (AssertableInertia $page) => $page
        ->where('legalValidated', true),
    );
});

it('affiche les accords dans leur version en vigueur', function (): void {
    $this->get('/consentements')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('public/Consents')
            // Un texte de consentement sans version datée rend inopposable
            // tout ce qui a été accepté avant.
            ->has('texts.0.version')
            ->has('texts.0.effectiveFrom')
            ->has('texts.0.body')
            ->where('texts.0.label', fn (mixed $label) => is_string($label)
                && ! str_starts_with($label, 'enums.')),
        );
});
