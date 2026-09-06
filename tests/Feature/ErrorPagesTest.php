<?php

declare(strict_types=1);

use App\Support\Brand;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Les pages d'erreur.
 *
 * Le projet n'en avait aucune : toute adresse fausse servait la page brute de
 * Laravel, une trace technique en anglais sur fond blanc (T-199). Ce que le
 * dossier demande tient en trois points — dire ce qui se passe en langage
 * simple, ne pas accuser la personne, proposer une reprise.
 *
 * Elles sont en Blade et **sans JavaScript** : elles doivent s'afficher quand
 * l'application ne va pas bien, y compris quand le bundle ne se charge pas.
 */
it('sert une page en français pour une adresse qui n’existe pas', function (): void {
    $reponse = $this->get('/une-adresse-qui-nexiste-pas');

    $reponse->assertNotFound()
        ->assertSee('Cette page n’existe pas', false)
        ->assertSee('Revenir à l’accueil', false)
        // Pas un mot d'anglais, pas de trace technique.
        ->assertDontSee('Not Found', false)
        ->assertDontSee('Whoops', false);
});

it('n’exige aucun script pour s’afficher', function (): void {
    $html = $this->get('/une-adresse-qui-nexiste-pas')->getContent();

    // Elle doit tenir quand le bundle ne se charge pas : c'est précisément
    // le moment où l'on voit une page d'erreur.
    expect($html)->not->toContain('<script');
});

it('porte le nom de marque des réglages, jamais en dur', function (): void {
    $html = (string) $this->get('/une-adresse-qui-nexiste-pas')->getContent();

    expect($html)->toContain(Brand::nameSafe());
});
