<?php

declare(strict_types=1);

use App\Models\AccessToken;
use App\Services\Tokens\TokenService;
use App\Settings\BrandSettings;
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

it('imprime les liens sur le domaine court, pas sur celui de l’application', function (): void {
    // Les deux réglages sont posés ensemble, parce que c'est leur **rapport**
    // qui décide de la forme du lien (`Links::base()`). Un test qui n'en pose
    // qu'un lit celui de la machine : celui-ci passait en local et échouait en
    // intégration continue, où `LINKS_DOMAIN` vaut `127.0.0.1`.
    config()->set('app.url', 'http://localhost:8001');

    $brand = app(BrandSettings::class);
    $brand->links_domain = 'localhost';
    $brand->save();

    $this->artisan('demo:liens')
        ->expectsOutputToContain('http://localhost:8001/r/')
        ->assertSuccessful();
});

it('suit le domaine court quand il diffère de celui de l’application', function (): void {
    // Le cas du tunnel : `laradev --tunnel` pose un domaine court distinct, et
    // la feuille doit imprimer des adresses ouvrables depuis un téléphone.
    config()->set('app.url', 'http://localhost:8001');

    $brand = app(BrandSettings::class);
    $brand->links_domain = 'exemple-tunnel.trycloudflare.com';
    $brand->save();

    $this->artisan('demo:liens')
        ->expectsOutputToContain('https://exemple-tunnel.trycloudflare.com/r/')
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

it('résout les identifiants du bloc 08 quand le décor est semé', function (): void {
    // Un identifiant de projet et un UUID d'histoire changent à chaque semis :
    // les écrire dans la feuille les rendrait faux le lendemain. La commande
    // les lit donc en base, et la feuille reste vraie.
    $this->seed(E2ELinksSeeder::class);

    $token = AccessToken::query()
        ->where('token_hash', TokenService::hash(E2ELinksSeeder::token('listen')))
        ->firstOrFail();

    $this->artisan('demo:liens', ['--bloc' => '08'])
        ->expectsOutputToContain((string) $token->subject->project_id)
        ->assertSuccessful();
});

it('reste utilisable quand le décor n’est pas semé', function (): void {
    // Sans décor, la commande doit dire quoi faire plutôt que planter : c'est
    // précisément l'état où l'on a le plus besoin d'elle.
    $this->artisan('demo:liens', ['--bloc' => '08'])
        ->expectsOutputToContain('migrate:fresh --seed')
        ->assertSuccessful();
});
