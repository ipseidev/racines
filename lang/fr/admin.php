<?php

declare(strict_types=1);

return [
    'roles' => [
        'admin' => 'Administration',
        'support' => 'Support',
        'support_readonly' => 'Support, lecture seule',
        'initiator' => 'Initiateur·rice',
    ],

    'brand' => [
        'title' => 'Marque',
        'saved' => 'Marque enregistrée.',
        'saved_help' => 'Les pages publiques et les messages utilisent déjà ces valeurs.',

        'identity' => 'Identité',
        'identity_help' => 'Le nom affiché partout : pages publiques, courriels, SMS, livre.',
        'product_name' => 'Nom du produit',
        'short_name' => 'Nom court',
        'tagline' => 'Promesse',
        'legal_entity' => 'Entité légale',
        'legal_address' => 'Adresse légale',

        'contacts' => 'Domaine et contacts',
        'contacts_help' => 'Un seul domaine court et stable pour tous les liens envoyés aux narrateurs.',
        'links_domain' => 'Domaine des liens',
        'links_domain_help' => 'Annoncé dès l’invitation. Ne jamais utiliser de raccourcisseur.',
        'support_email' => 'Courriel du support',
        'support_phone' => 'Téléphone du support',
        'sms_sender_id' => 'Expéditeur des SMS',
        'sms_sender_id_help' => '3 à 11 caractères alphanumériques, au moins une lettre. Constant, pour être reconnu.',

        'colors' => 'Couleurs',
        'colors_help' => 'Une combinaison illisible est refusée : le seuil est de 4,5 pour 1.',
        'color_primary' => 'Couleur principale',
        'color_primary_foreground' => 'Texte sur couleur principale',
        'color_accent' => 'Couleur d’accent',
        'color_accent_foreground' => 'Texte sur couleur d’accent',
        'color_background' => 'Couleur du fond',
        'color_surface' => 'Couleur des surfaces',
        'color_text' => 'Couleur du texte',
        'color_muted' => 'Couleur du texte secondaire',

        'typography' => 'Typographie',
        'font_display' => 'Police des titres',
        'font_body' => 'Police du texte',
    ],
];
