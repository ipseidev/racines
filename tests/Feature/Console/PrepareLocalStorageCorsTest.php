<?php

declare(strict_types=1);

use App\Console\Commands\PrepareLocalStorage;

/**
 * L'origine de l'application est toujours une origine légitime pour son
 * propre seau de médias.
 *
 * Trouvé en préparant le checkpoint du bloc 08 sur un vrai téléphone. Les
 * origines CORS vivaient uniquement dans `docker/minio/cors.json`, un fichier
 * suivi par git et figé sur `http://localhost:8001`. Dès que l'application est
 * servie ailleurs — l'IP du Mac pour un téléphone du même wifi, un tunnel
 * Cloudflare pour le spike du bloc 04, une préproduction — le navigateur
 * refuse le `PUT` présigné et l'audio reste muet, **sans message** : un refus
 * CORS ne dit rien à l'application, seulement à la console du navigateur.
 *
 * Trois blocs butaient sur le même obstacle (04, 08, 12). Plutôt que de faire
 * modifier un fichier suivi à chaque changement d'adresse, l'origine de
 * `APP_URL` rejoint les règles.
 */
function corsOrigins(): array
{
    $rules = (fn () => $this->corsRules())->call(new PrepareLocalStorage);

    return $rules['CORSRules'][0]['AllowedOrigins'] ?? [];
}

it('fait entrer l’origine de l’application dans les règles', function (): void {
    config(['app.url' => 'http://192.168.1.88:8001']);

    expect(corsOrigins())->toContain('http://192.168.1.88:8001');
});

it('garde les origines du fichier', function (): void {
    config(['app.url' => 'http://192.168.1.88:8001']);

    expect(corsOrigins())->toContain('http://localhost:8001');
});

it('n’inscrit pas deux fois la même origine', function (): void {
    config(['app.url' => 'http://localhost:8001']);

    $origins = corsOrigins();

    expect(array_count_values($origins)['http://localhost:8001'] ?? 0)->toBe(1);
});

it('ne garde que le schéma, l’hôte et le port', function (): void {
    config(['app.url' => 'https://essai.trycloudflare.com/sous/chemin']);

    expect(corsOrigins())->toContain('https://essai.trycloudflare.com')
        ->not->toContain('https://essai.trycloudflare.com/sous/chemin');
});
