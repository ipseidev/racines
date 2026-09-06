<?php

return [

    /*
     * La traduction française du paquet est en retard sur l'anglaise : cette
     * clé n'y figure pas, et comme la locale de repli est le français elle
     * aussi, rien ne la rattrapait — l'interface affichait
     * `filament-panels::layout.skip_to_content.label` en toutes lettres
     * (T-181). Ce fichier surcharge celui du paquet **en entier** : une clé
     * ajoutée en amont n'apparaîtra pas ici, il faudra la reporter.
     */
    'skip_to_content' => [
        'label' => 'Aller au contenu',
    ],

    'direction' => 'ltr',

    'actions' => [

        'billing' => [
            'label' => "Gérer l'abonnement",
        ],

        'logout' => [
            'label' => 'Déconnexion',
        ],

        'open_database_notifications' => [
            'label' => 'Ouvrir les notifications',
        ],

        'open_user_menu' => [
            'label' => 'Menu utilisateur',
        ],

        'sidebar' => [

            'collapse' => [
                'label' => 'Réduire la barre latérale',
            ],

            'expand' => [
                'label' => 'Agrandir la barre latérale',
            ],

        ],

        'theme_switcher' => [

            'dark' => [
                'label' => 'Activer le mode sombre',
            ],

            'light' => [
                'label' => 'Désactiver le mode sombre',
            ],

            'system' => [
                'label' => 'Activer le thème système',
            ],

        ],

    ],

    'avatar' => [
        'alt' => 'Avatar de :name',
    ],

    'logo' => [
        'alt' => 'Logo de :name',
    ],

];
