<?php

declare(strict_types=1);

use Database\Seeders\E2ELinksSeeder;
use PragmaRX\Google2FA\Google2FA;

/**
 * Le code du second facteur du décor.
 *
 * Le compte d'administration a son second facteur configuré d'avance, sur un
 * secret constant, pour que la suite bout en bout puisse se connecter (T-180).
 * L'écran demande donc un code que personne n'a dans son téléphone, et deux
 * vérifications humaines se sont arrêtées là — le blocage n'est pas dans le
 * produit, il est dans l'outillage (T-188).
 */
it('refuse de tourner en production', function (): void {
    app()->detectEnvironment(fn (): string => 'production');

    $this->artisan('demo:totp')->assertFailed();
});

it('imprime un code qui ouvre vraiment le compte', function (): void {
    $attendu = (new Google2FA)->getCurrentOtp(E2ELinksSeeder::E2E_TOTP_SECRET);

    $this->artisan('demo:totp')
        ->expectsOutputToContain($attendu)
        ->assertSuccessful();
});
