<?php

declare(strict_types=1);

use App\Support\Brand;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/*
 * Une réponse qui n'est pas une page ne devient pas celle où l'on revient.
 *
 * `StartSession` note l'adresse de toute requête `GET` non-ajax comme « page
 * précédente », sans regarder ce qu'elle a rendu. Le navigateur demande le
 * manifeste de lui-même, à un moment qu'il choisit, et la dernière gagne : le
 * `back()` du formulaire suivant y retournait, et Inertia — qui suit la
 * redirection — recevait le manifeste en JSON. C'est ce qu'un narrateur a lu
 * après avoir cliqué « Partager avec mes proches » (T-231).
 */
it('laisse la page précédente intacte quand le navigateur prend le manifeste', function (): void {
    // Une vraie page d'abord : c'est elle qu'on doit retrouver.
    $this->get('/');

    $accueil = session()->previousUrl();

    expect($accueil)->not->toBeNull();

    $this->get('/site.webmanifest')->assertOk();

    // Le manifeste est passé, et n'a rien pris.
    expect(session()->previousUrl())->toBe($accueil)
        ->and(session()->previousUrl())->not->toContain('webmanifest');
});

it('laisse la page précédente intacte quand on enregistre la fiche contact', function (): void {
    // La fiche est proposée juste avant que le narrateur aille enregistrer
    // son histoire : c'est la pire place pour voler la page précédente.
    $this->get('/');
    $this->get('/vcard')->assertOk();

    expect(session()->previousUrl())->not->toContain('vcard');
});

it('n’empêche pas une vraie page de devenir la page précédente', function (): void {
    // La garde ne doit pas se généraliser toute seule : une page reste une
    // page, et `back()` doit y revenir. C'est la moitié du contrat.
    $this->get('/site.webmanifest');
    $this->get('/');

    expect(session()->previousUrl())->not->toContain('webmanifest');

    $this->get('/vcard');
    $this->get('/');

    expect(session()->previousUrl())->not->toContain('vcard');
});

it('rend bien le manifeste, avec le nom des réglages', function (): void {
    $reponse = $this->get('/site.webmanifest');

    $reponse->assertOk()
        ->assertHeader('Content-Type', 'application/manifest+json');

    expect($reponse->json('name'))->toBe(Brand::name());
});
