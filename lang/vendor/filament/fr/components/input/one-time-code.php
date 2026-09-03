<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Saisie du code à usage unique de Filament
|--------------------------------------------------------------------------
|
| Le paquet ne fournit pas encore ce fichier en français : l'écran de double
| authentification du back-office affichait la clé brute
| « filament::components/input/one-time-code.aria_label » comme libellé
| accessible de chacune des six cases. Repéré par la capture d'un test bout en
| bout, corrigé ici en attendant que le paquet le livre.
|
| L'espace de noms est `filament` : c'est le nom déclaré par
| `SupportServiceProvider`, et non celui du paquet Composer.
|
*/

return [
    'aria_label' => 'Caractère :position sur :count',
];
