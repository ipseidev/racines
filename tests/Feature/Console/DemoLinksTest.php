<?php

declare(strict_types=1);

use Database\Seeders\E2ELinksSeeder;

/**
 * La feuille de route des vérifications humaines.
 *
 * Six checkpoints attendent quelqu'un devant un navigateur. Chacun demande un
 * lien à jeton de quarante-trois caractères, un téléphone connu ou un code à
 * six chiffres, et rien de tout cela ne se retient. La commande imprime la
 * feuille ; ces tests vérifient qu'elle imprime la **vraie** valeur, parce
 * qu'une feuille de test fausse coûte plus cher que pas de feuille du tout.
 */
it('imprime les liens du bloc 07 avec leur valeur réelle', function (): void {
    $this->artisan('demo:liens')
        ->expectsOutputToContain(E2ELinksSeeder::token('variant-a'))
        ->expectsOutputToContain(E2ELinksSeeder::token('variant-b'))
        ->expectsOutputToContain(E2ELinksSeeder::token('withdraw'))
        ->assertSuccessful();
});

it('imprime les coordonnées et le code de l’espace narrateur', function (): void {
    $this->artisan('demo:liens')
        ->expectsOutputToContain(E2ELinksSeeder::SPACE_NARRATORS['space'])
        ->expectsOutputToContain(E2ELinksSeeder::SPACE_CODE)
        ->assertSuccessful();
});

it('imprime les comptes du back-office et de l’espace Initiateur·rice', function (): void {
    $this->artisan('demo:liens')
        ->expectsOutputToContain((string) config('product.seeding.admin_email'))
        ->expectsOutputToContain(E2ELinksSeeder::INITIATOR_EMAIL)
        ->expectsOutputToContain(E2ELinksSeeder::E2E_TOTP_SECRET)
        ->assertSuccessful();
});

it('n’imprime que des adresses du domaine des liens local', function (): void {
    config()->set('app.url', 'http://localhost:8001');

    $this->artisan('demo:liens')
        ->expectsOutputToContain('http://localhost:8001/r/')
        ->assertSuccessful();
});

it('refuse de tourner en production', function (): void {
    app()->detectEnvironment(fn (): string => 'production');

    $this->artisan('demo:liens')->assertFailed();
});

it('se limite à un bloc quand on le lui demande', function (): void {
    $this->artisan('demo:liens', ['--bloc' => '07'])
        ->expectsOutputToContain(E2ELinksSeeder::token('variant-a'))
        ->doesntExpectOutputToContain(E2ELinksSeeder::token('listen'))
        ->assertSuccessful();
});
