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

    /*
    |--------------------------------------------------------------------------
    | Back-office : les ressources
    |--------------------------------------------------------------------------
    |
    | Le vocabulaire du support est celui de quelqu'un qui aide, pas de
    | quelqu'un qui administre des lignes : « la famille », « où en est
    | l'histoire », « à la demande de qui ». Les mots qu'on lit toute la
    | journée finissent par décider comment on se comporte.
    |
    */

    'stories' => [
        'title' => 'Les histoires',
        'singular' => 'Histoire',
        'identity' => 'L’histoire',
        'timeline' => 'Les dates',
        'question' => 'La question posée',
        'state' => 'Où en est',
        'story_title' => 'Titre',
        'sequence' => 'N°',
        'family' => 'La famille',
        'recorded_at' => 'Enregistrée le',
        'validated_at' => 'Validée le',
        'shared_at' => 'Partagée le',
        'actions' => [
            'hide' => 'Masquer',
            'trash' => 'Mettre à la corbeille',
            'restore' => 'Remettre',
            'reason' => 'À la demande de qui, et pourquoi ?',
            'reason_help' => 'Une action sur le récit de quelqu’un ne se fait qu’à sa demande, ou à celle de l’Initiateur·rice. Ce motif en est la trace.',
            'done' => 'C’est fait.',
        ],
    ],

    'playbooks' => [
        'title' => 'Les playbooks',
        'empty' => 'Aucun playbook pour l’instant.',
    ],

    'pilot' => [
        'title' => 'Le pilote',
        'offer' => 'L’offre',
        'offer_help' => 'Le mode gouverne ce que la page d’accueil annonce et ce que le tunnel vend. Le changer agit immédiatement, pour tous les visiteurs.',
        'mode' => 'Mode de vente',
        'mode_pilot' => 'Offre pilote',
        'mode_prevente' => 'Prévente',
        'mode_core' => 'Offre courante',
        'cohort' => 'Cohorte en cours',
        'prices' => 'Les prix',
        'prices_help' => 'En centimes, comme en base. Changer un prix ici ne change pas le prix correspondant dans Stripe : un prix Stripe ne se modifie pas, il se remplace.',
        'pilot_price' => 'Offre pilote (centimes)',
        'extra_copy_price' => 'Exemplaire supplémentaire (centimes)',
        'extra_copy_price_help' => 'À confirmer avec le devis de l’imprimeur : c’est le seul prix dont la marge dépend d’un tiers.',
        'phone_option_price' => 'Enregistrement par téléphone (centimes)',
        'phone_option_cap' => 'Plafond de l’option téléphone',
        'phone_option_cap_help' => 'Bloquant côté serveur. Une promesse humaine faite à plus de familles qu’on ne peut en rappeler vaut moins qu’une promesse jamais faite.',
        'gift_send_hour' => 'Heure d’envoi des cadeaux',
        'legal' => 'Validation juridique',
        'legal_help' => 'Tant que cette date est vide, les pages légales portent leur bandeau « à valider par conseil ».',
        'legal_validated_at' => 'Textes validés le',
        'legal_validated_at_help' => 'Poser cette date fait disparaître le bandeau de toutes les pages publiques. Ne la posez qu’après une relecture réelle par un conseil : sinon vous retirez un avertissement qui est vrai.',
    ],
];
