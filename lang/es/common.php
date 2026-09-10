<?php

declare(strict_types=1);

return [
    /*
     * Le sélecteur de langue. Dans `common` parce qu'il vit dans les quatre
     * espaces : le pied de page public, l'espace, la page famille et la page
     * narrateur.
     */
    'locale' => [
        'label' => 'Elegir el idioma',
    ],

    'actions' => [
        'back' => 'Volver',
        'cancel' => 'Cancelar',
        'close' => 'Cerrar',
        'continue' => 'Continuar',
        'retry' => 'Reintentar',
        'save' => 'Guardar',
        'sending' => 'Enviando…',
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

    /*
     * Le lecteur audio, le même pour la narratrice qui se réécoute et pour la
     * famille qui écoute (T-138). Ici et non dans `family` : les pages
     * narratrice n'emportent que `common` et leur propre fichier.
     */
    'player' => [
        'play' => 'Escuchar',
        'pause' => 'Pausar',
        'back15' => 'Retroceder 15 segundos',
        'forward15' => 'Avanzar 15 segundos',
        'slower' => 'Un poco más despacio',
        'normal' => 'Velocidad normal',
        'remaining' => 'Quedan :time',
        'elapsed' => ':time',
        'progress' => 'Progreso de la reproducción',
    ],

    /*
     * Le lecteur vidéo (T-210). Il garde les commandes natives du navigateur,
     * qui savent le plein-écran et l'incrustation mieux qu'une barre maison :
     * il n'a donc besoin que d'un nom, pour les lecteurs d'écran.
     */
    'video' => [
        'label' => 'El relato en vídeo',
        'self_view' => 'Lo que ve la cámara',
    ],

    'photos' => [
        'title' => 'Las fotos',
        'add' => 'Añadir una foto',
        'add_help' => 'Desde su galería, o haciendo la foto ahora.',
        'caption' => '¿Qué se ve en esta foto?',
        'caption_help' => 'Este pie de foto aparecerá bajo la imagen en el libro.',
        'added' => 'La foto se ha añadido.',
        'added_small' => 'La foto se ha añadido. Es un poco pequeña para imprimirla, pero se verá bien en línea.',
        'caption_saved' => 'El pie de foto se ha guardado.',
        'removed' => 'La foto se ha quitado.',
        'remove' => 'Quitar esta foto',
        'not_print_ready' => 'Un poco pequeña para imprimir',
        'later' => 'Más tarde',
    ],
];
