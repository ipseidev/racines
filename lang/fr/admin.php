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

    'projects' => [
        'title' => 'Les projets',
        'singular' => 'Projet',
        'identity' => 'Le projet',
        'rhythm' => 'Le rythme',
        'status' => 'Statut',
        'narrator' => 'Narrateur',
        'initiator' => 'Initiateur·rice',
        'cadence' => 'Cadence',
        'next_prompt' => 'Prochaine question',
        'paused_until' => 'En pause jusqu’au',
        'collection_ends' => 'Fin de la collecte',
        'stories_count' => 'Histoires',
        'actions' => [
            'pause' => 'Mettre en pause',
            'resume' => 'Reprendre maintenant',
            'reschedule' => 'Replanifier la prochaine question',
            'weeks' => 'Combien de semaines ?',
            'weeks_help' => 'Une pause a toujours un terme, et le narrateur en est prévenu.',
            'at' => 'Quand ?',
            'freeze' => 'Geler (décès)',
            'freeze_help' => 'Arrête immédiatement les questions, les relances, les notifications et les règles du moteur. Le dégel est une décision de l’administration, après lecture des directives et sur demande écrite de la famille.',
            'freeze_reason' => 'Qui nous a prévenus, et comment ?',
            'done' => 'C’est fait.',
        ],
    ],

    'tickets' => [
        'title' => 'La file du support',
        'singular' => 'Ticket',
        'kind' => 'Motif',
        'family' => 'La famille',
        'status' => 'État',
        'opened_at' => 'Ouvert',
        'actions' => [
            'close' => 'Clore',
            'note' => 'Qu’avez-vous fait ?',
            'note_help' => 'Cette note se relit si la même famille revient : elle évite de repartir de zéro.',
            'done' => 'Ticket clos.',
        ],
    ],

    'orders' => [
        'title' => 'Les commandes',
        'singular' => 'Commande',
        'buyer' => 'Acheteur',
        'status' => 'État',
        'total' => 'Total',
        'refunded' => 'Remboursé',
        'withdrawal_deadline' => 'Rétractation jusqu’au',
        'actions' => [
            'refund' => 'Rembourser',
            'refund_help' => 'Le remboursement est irréversible et déplace de l’argent. Le montant proposé est ce qui reste remboursable.',
            'amount' => 'Montant en centimes',
            'amount_help' => 'Au plus ce qui reste remboursable sur cette commande.',
            'reason' => 'Pourquoi ce remboursement ?',
            'done' => 'Demande transmise.',
            'done_help' => 'L’état de la commande se mettra à jour à la réception de la confirmation du prestataire.',
            'failed' => 'Remboursement refusé',
        ],
    ],

    'groups' => [
        'registers' => 'Les registres',
        'reference' => 'Les référentiels',
    ],

    'narrators' => [
        'title' => 'Les narrateurs',
        'singular' => 'Narrateur',
        'first_name' => 'Prénom',
        'family' => 'La famille',
        'channel' => 'Canal',
        'opted_in_at' => 'A accepté le',
        'contact_deleted_at' => 'Coordonnées effacées le',
    ],

    'family' => [
        'title' => 'Les proches',
        'singular' => 'Proche',
        'name' => 'Prénom',
        'family' => 'La famille',
        'relationship' => 'Lien de parenté',
        'can_contribute' => 'Peut contribuer',
        'first_seen_at' => 'A ouvert son lien le',
        'never_opened' => 'Jamais ouvert',
        'removed' => 'Retiré',
        'actions' => [
            'reissue' => 'Réémettre le lien',
            'reissue_help' => 'Le lien précédent cesse de fonctionner. Le nouveau part sur le canal de la personne.',
            'reissued' => 'Lien réémis.',
            'reissued_help' => 'Le précédent ne fonctionne plus.',
            'remove' => 'Retirer l’accès',
            'removed' => 'Accès retiré.',
        ],
    ],

    'tokens' => [
        'title' => 'Les liens émis',
        'singular' => 'Lien',
        'type' => 'Type',
        'subject' => 'Porte sur',
        'created_at' => 'Émis le',
        'expires_at' => 'Expire le',
        'use_count' => 'Utilisations',
        'revoked' => 'Révoqué',
        'actions' => [
            'revoke' => 'Révoquer',
            'revoke_help' => 'Le lien cesse immédiatement de fonctionner. La personne devra en demander un nouveau.',
            'done' => 'Lien révoqué.',
        ],
    ],

    'messages' => [
        'title' => 'Les envois',
        'singular' => 'Envoi',
        'template' => 'Message',
        'channel' => 'Canal',
        'status' => 'État',
        'created_at' => 'Envoyé le',
        'delivered_at' => 'Reçu le',
    ],

    'engine' => [
        'title' => 'Le moteur',
        'singular' => 'Événement',
        'rule' => 'Règle',
        'family' => 'La famille',
        'fired_at' => 'Déclenchée le',
        'outcome' => 'Suite',
    ],

    'phone_options' => [
        'title' => 'Option téléphone',
        'singular' => 'Option téléphone',
        'family' => 'La famille',
        'status' => 'État',
        'entry' => 'Origine',
        'call_day' => 'Jour d’appel',
        'call_slot' => 'Créneau',
    ],

    'consent_texts' => [
        'title' => 'Les textes de consentement',
        'singular' => 'Texte de consentement',
        'kind' => 'Accord',
        'version' => 'Version',
        'locale' => 'Langue',
        'effective_from' => 'En vigueur depuis',
    ],

    'questions' => [
        'title' => 'Le corpus',
        'singular' => 'Question',
        'order' => 'Ordre',
        'text' => 'La question',
        'theme' => 'Thème',
        'difficulty' => 'Intimité',
    ],

    'cohorts' => [
        'title' => 'Les cohortes',
        'singular' => 'Cohorte',
        'name' => 'Nom',
        'phase' => 'Phase',
        'started_at' => 'Démarrée le',
    ],

    'users' => [
        'title' => 'Les comptes',
        'singular' => 'Compte',
        'name' => 'Nom',
        'email' => 'Courriel',
        'role' => 'Rôle',
        'mfa' => 'Double authentification',
        'actions' => [
            'role' => 'Changer le rôle',
            'done' => 'Rôle changé.',
        ],
    ],

    'transcripts' => [
        'title' => 'Les textes',
        'singular' => 'Texte',
        'kind' => 'Rendu',
        'family' => 'La famille',
        'version' => 'Version',
        'current' => 'En cours',
        'created_at' => 'Créé le',
        'actions' => [
            'edit' => 'Corriger le texte',
            'edit_help' => 'La correction crée une nouvelle version. Le mot à mot d’origine n’est pas touché, et l’ancienne version reste lisible.',
            'text' => 'Le texte',
            'done' => 'Correction enregistrée.',
            'done_help' => 'Une nouvelle version a été créée. Le mot à mot est intact.',
        ],
    ],
];
