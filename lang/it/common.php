<?php

declare(strict_types=1);

return [
    /*
     * Le sélecteur de langue. Dans `common` parce qu'il vit dans les quatre
     * espaces : le pied de page public, l'espace, la page famille et la page
     * narrateur.
     */
    'locale' => [
        'label' => 'Scegli la lingua',
    ],

    'actions' => [
        'back' => 'Indietro',
        'cancel' => 'Annulla',
        'close' => 'Chiudi',
        'continue' => 'Continua',
        'retry' => 'Riprova',
        'save' => 'Salva',
        'sending' => 'Invio…',
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
        'play' => 'Ascolta',
        'pause' => 'Metti in pausa',
        'back15' => 'Indietro di 15 secondi',
        'forward15' => 'Avanti di 15 secondi',
        'slower' => 'Rallenta un po’',
        'normal' => 'Velocità normale',
        'remaining' => 'Mancano :time',
        'elapsed' => ':time',
        'progress' => 'Avanzamento dell’ascolto',
    ],

    /*
     * Le lecteur vidéo (T-210). Il garde les commandes natives du navigateur,
     * qui savent le plein-écran et l'incrustation mieux qu'une barre maison :
     * il n'a donc besoin que d'un nom, pour les lecteurs d'écran.
     */
    'video' => [
        'label' => 'Il racconto filmato',
        'self_view' => 'Ciò che vede la fotocamera',
    ],

    'photos' => [
        'title' => 'Le foto',
        'add' => 'Aggiungi una foto',
        'add_help' => 'Dalla sua galleria, oppure scattandola adesso.',
        'caption' => 'Che cosa si vede in questa foto?',
        'caption_help' => 'Questa didascalia apparirà sotto l’immagine nel libro.',
        'added' => 'La foto è stata aggiunta.',
        'added_small' => 'La foto è stata aggiunta. È un po’ piccola per la stampa, ma resterà leggibile online.',
        'caption_saved' => 'La didascalia è stata salvata.',
        'removed' => 'La foto è stata tolta.',
        'remove' => 'Togli questa foto',
        'not_print_ready' => 'Un po’ piccola per la stampa',
        'later' => 'Più tardi',
    ],
];
