<?php

declare(strict_types=1);

use App\Support\Storage\BrowserUploadCors;

/*
 * Le jugement porté sur une règle CORS.
 *
 * La règle elle-même vit dans la console du fournisseur, hors de portée d'un
 * test — et MinIO ne sert même pas les siennes par l'API S3 (T-58). Ce qui est
 * éprouvable est donc le jugement, et il porte sur trois conditions dont la
 * troisième est celle qu'on oublie : sans `ExposeHeaders: ETag`, le dépôt
 * réussit, le navigateur cache l'en-tête, l'envoi multipart ne peut pas se
 * conclure, et le narrateur lit « L'envoi n'a pas abouti » après avoir parlé
 * — les journaux du serveur restant vides, puisque rien n'a échoué chez nous
 * (T-224).
 */
$complete = [[
    'AllowedOrigins' => ['https://exemple.fr'],
    'AllowedMethods' => ['PUT', 'GET', 'HEAD'],
    'AllowedHeaders' => ['*'],
    'ExposeHeaders' => ['ETag'],
]];

it('ne reproche rien à une règle complète', function () use ($complete): void {
    expect(BrowserUploadCors::missing($complete, 'https://exemple.fr'))->toBe([]);
});

it('voit l’ETag non exposé, le défaut qui échoue en silence', function () use ($complete): void {
    $sansEtag = $complete;
    unset($sansEtag[0]['ExposeHeaders']);

    expect(BrowserUploadCors::missing($sansEtag, 'https://exemple.fr'))
        ->toBe(['l’exposition de l’en-tête ETag']);
});

it('voit une origine absente, et la nomme', function () use ($complete): void {
    // Le piège de production : la règle porte le domaine de l'application,
    // alors que la page d'enregistrement est servie sur le domaine court.
    expect(BrowserUploadCors::missing($complete, 'https://liens.exemple.fr'))
        ->toBe(['l’origine https://liens.exemple.fr']);
});

it('voit PUT absent', function (): void {
    $lecture = [[
        'AllowedOrigins' => ['https://exemple.fr'],
        'AllowedMethods' => ['GET', 'HEAD'],
        'ExposeHeaders' => ['ETag'],
    ]];

    expect(BrowserUploadCors::missing($lecture, 'https://exemple.fr'))->toBe(['la méthode PUT']);
});

it('accepte le joker, qui est un choix et non un oubli', function (): void {
    $joker = [[
        'AllowedOrigins' => ['*'],
        'AllowedMethods' => ['*'],
        'ExposeHeaders' => ['*'],
    ]];

    expect(BrowserUploadCors::missing($joker, 'https://exemple.fr'))->toBe([]);
});

it('ignore la casse d’un en-tête, qui n’y est pas sensible', function (): void {
    $minuscules = [[
        'AllowedOrigins' => ['https://exemple.fr'],
        'AllowedMethods' => ['put'],
        'ExposeHeaders' => ['etag'],
    ]];

    expect(BrowserUploadCors::missing($minuscules, 'https://exemple.fr'))->toBe([]);
});

it('lit une absence de règle comme un manque total', function (): void {
    expect(BrowserUploadCors::missing(null, 'https://exemple.fr'))->toBe(['toute règle CORS'])
        ->and(BrowserUploadCors::missing([], 'https://exemple.fr'))->toBe(['toute règle CORS']);
});

it('cumule les manques plutôt que de s’arrêter au premier', function (): void {
    // Une règle posée à la va-vite : on la corrige d'un coup, pas en trois
    // allers-retours dans la console.
    $nue = [['AllowedOrigins' => ['https://autre.fr'], 'AllowedMethods' => ['GET']]];

    expect(BrowserUploadCors::missing($nue, 'https://exemple.fr'))->toHaveCount(3);
});

it('rend une règle qu’on peut coller telle quelle', function (): void {
    $suggestion = implode('', BrowserUploadCors::suggestion('https://exemple.fr'));

    expect(json_decode($suggestion, true))->toBeArray()
        ->and($suggestion)->toContain('"ExposeHeaders":["ETag"]')
        ->and($suggestion)->toContain('https://exemple.fr');
});
