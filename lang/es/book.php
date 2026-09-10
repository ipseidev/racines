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
    'foreword' => 'Prólogo',
    'contents' => 'Índice',
    'chapter_number' => 'Capítulo :number',
    'qr_legend' => 'Escanee para escuchar a :first_name contar esta historia.',
    'untitled' => 'Sin título',
    // Ce qu'on cherche sur un livre de famille trente ans plus tard.
    'collected' => 'Relatos recogidos en :year',

    'colophon' => [
        // La mention D-8 : une durée annoncée, jamais « pour toujours »
        // (R-11). Le pack hors-ligne est la contrepartie de cette durée.
        'qr_commitment' => 'Los códigos QR de este libro funcionan hasta el :date y pueden prolongarse; se le ha entregado una copia sin conexión de las grabaciones.',
        'rendering' => 'Texto puesto en limpio a partir de la voz de :first_name, releído y validado por :first_name.',
        'brand' => ':brand — :domain',
    ],
];
