<?php

declare(strict_types=1);

return [
    'actions' => [
        'back' => 'Retour',
        'cancel' => 'Annuler',
        'close' => 'Fermer',
        'continue' => 'Continuer',
        'retry' => 'Réessayer',
        'save' => 'Enregistrer',
    ],

    /*
    |--------------------------------------------------------------------------
    | Photos (bloc 12)
    |--------------------------------------------------------------------------
    |
    | Dans `common` et non dans un espace : les quatre espaces — narrateur par
    | jeton, espace narrateur, page famille, tableau de bord — affichent le
    | même dépôt de photo, et `Translations::forSpace` n'envoie au front que
    | `common` plus le fichier de l'espace courant. Rangées ailleurs, elles
    | s'affichaient en clé brute sur trois des quatre pages.
    |
    | « La photo, l'histoire et la voix sur une même page » est le cœur du
    | produit imprimé. Ce qui arrive du téléphone est souvent une photo **de**
    | photo : mal cadrée, un peu petite. On accepte et on prévient, plutôt que
    | de refuser — c'est peut-être la seule image qui existe de quelqu'un.
    |
    */

    'photos' => [
        'title' => 'Les photos',
        'add' => 'Ajouter une photo',
        'add_help' => 'Depuis votre galerie, ou en prenant la photo maintenant.',
        'caption' => 'Que voit-on sur cette photo ?',
        'caption_help' => 'Cette légende apparaîtra sous l’image dans le livre.',
        'added' => 'La photo est ajoutée.',
        'added_small' => 'La photo est ajoutée. Elle est un peu petite pour l’impression, mais elle restera lisible en ligne.',
        'caption_saved' => 'La légende est enregistrée.',
        'removed' => 'La photo est retirée.',
        'remove' => 'Retirer cette photo',
        'not_print_ready' => 'Un peu petite pour l’impression',
        'later' => 'Plus tard',
    ],
];
