<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Le livre imprimé
|--------------------------------------------------------------------------
|
| Les textes qui partent à l'impression. Ils sont définitifs au sens propre :
| une coquille ici est une coquille sur trente exemplaires. Le nom de marque
| n'apparaît jamais en dur — il vient des réglages (`Brand`).
|
*/

return [
    'foreword' => 'Avant-propos',
    'contents' => 'Sommaire',
    'chapter_number' => 'Chapitre :number',
    'qr_legend' => 'Scannez pour entendre :first_name raconter.',
    'untitled' => 'Sans titre',
    // Ce qu'on cherche sur un livre de famille trente ans plus tard.
    'collected' => 'Récits recueillis en :year',

    /*
     * Les formules de titre de couverture (T-241).
     *
     * `:of` arrive déjà élidé — « de Jeanne », « d'Odette » —, composé par
     * `Names::of()` côté serveur et par son jumeau `ofName()` côté écran. Une
     * formule ne colle donc jamais « de » elle-même : elle recevrait
     * « L'histoire de Odette ».
     */
    'title' => [
        'first_name' => ':name',
        'story' => 'L’histoire :of',
        'stories' => 'Les histoires :of',
        'life' => 'La vie :of',
        'memories' => 'Les souvenirs :of',
    ],

    'colophon' => [
        // La mention D-8 : une durée annoncée, jamais « pour toujours »
        // (R-11). Le pack hors-ligne est la contrepartie de cette durée.
        'qr_commitment' => 'Les QR de ce livre fonctionnent jusqu’au :date et peuvent être prolongés ; un pack hors-ligne des enregistrements vous a été remis.',
        'rendering' => 'Texte mis au propre à partir de la voix de :first_name, relu et validé par :first_name.',
        'brand' => ':brand — :domain',
    ],
];
