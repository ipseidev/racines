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
    'foreword' => 'Prefazione',
    'contents' => 'Indice',
    'chapter_number' => 'Capitolo :number',
    'qr_legend' => 'Inquadra il codice per ascoltare :first_name raccontare.',
    'untitled' => 'Senza titolo',
    // Ce qu'on cherche sur un livre de famille trente ans plus tard.
    'collected' => 'Racconti raccolti nel :year',

    'colophon' => [
        // La mention D-8 : une durée annoncée, jamais « pour toujours »
        // (R-11). Le pack hors-ligne est la contrepartie de cette durée.
        'qr_commitment' => 'I codici QR di questo libro funzionano fino al :date e possono essere prolungati; alla famiglia è stato consegnato un archivio offline delle registrazioni.',
        'rendering' => 'Testo messo in bella copia a partire dalla voce di :first_name, riletto e approvato da :first_name.',
        'brand' => ':brand — :domain',
    ],
];
