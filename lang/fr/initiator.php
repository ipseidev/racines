<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Espace de l'Initiateur·rice
|--------------------------------------------------------------------------
|
| Les actions en un tap du moteur de complétion (bloc 09). Le ton n'y met
| jamais en cause : on propose un geste, on ne reproche pas un silence. La
| personne qui lit ces pages a acheté le service et porte le projet — la
| fatiguer, c'est perdre le meilleur relais du produit.
|
*/

return [

    'status' => [
        'draft' => 'En préparation',
        'awaiting_acceptance' => 'En attente de la réponse de votre proche',
        'active' => 'En cours',
        'paused' => 'En pause',
        'dormant' => 'En sommeil',
        'completed' => 'Terminé',
        'cancelled' => 'Annulé',
        'frozen_bereavement' => 'Suspendu',
    ],

    /*
     * L'état d'une histoire, du point de vue de l'Initiateur·rice. Elle voit
     * **où en est** chaque histoire, jamais son contenu tant que le narrateur
     * ne l'a pas partagée.
     */
    'story_state' => [
        'proposed' => 'Question envoyée',
        'recorded' => 'Enregistrée',
        'transcribed' => 'Gardée par votre proche',
        'to_review' => 'En attente de son choix',
        'validated' => 'Validée',
        'shared' => 'Partagée avec vous',
        'in_book' => 'Dans le livre',
        'hidden' => 'Masquée par votre proche',
        'archived' => 'Archivée',
        'trashed' => 'Dans la corbeille',
        'deleted' => 'Supprimée',
    ],

    'alert' => [
        'invitation_not_accepted' => 'L’invitation n’a pas encore été ouverte. Un message de vous aiderait.',
        'three_stories_no_reaction' => 'Trois histoires partagées, aucune réaction. Un cœur suffirait.',
        'narrator_silence_21d' => 'Pas d’enregistrement depuis trois semaines. Un coup de fil débloque souvent les choses.',
    ],

    'copy_link' => [
        'ready' => 'Voici le lien. Collez-le dans votre message.',
        'no_story' => 'Aucune question en cours pour l’instant.',
        'no_family_member' => 'Vous n’avez pas encore de lien d’écoute.',
        'whatsapp' => 'Bonjour, voici le lien pour enregistrer votre histoire : :link',
    ],

    'one_tap' => [
        'expired' => 'Ce lien a déjà servi. Vous pouvez agir depuis votre espace.',

        'resend_whatsapp' => [
            'title' => 'Renvoyer le lien vous-même',
            'body' => 'Un message venant de vous se remarque bien plus qu’un SMS d’un numéro inconnu. Voici le lien à transmettre.',
            'button' => 'Obtenir le lien',
            'done' => 'Voici le lien. Collez-le dans votre message.',
            'message' => 'Bonjour, voici le lien pour enregistrer votre histoire : :link',
            'audio_hint' => 'Un message vocal de trente secondes fonctionne encore mieux : votre voix se reconnaît.',
            'no_question' => 'Toutes les questions ont déjà été posées. Ajoutez-en une depuis votre espace.',
        ],

        'switch_biweekly' => [
            'title' => 'Une question toutes les deux semaines',
            'body' => 'Une question par semaine, c’est peut-être beaucoup. Réduire le rythme vaut mieux qu’arrêter, et le livre se construit tout aussi bien.',
            'button' => 'Passer à toutes les deux semaines',
            'done' => 'C’est fait : une question toutes les deux semaines.',
        ],

        'ack_call_parent' => [
            'title' => 'Vous appelez vous-même',
            'body' => 'Un coup de fil débloque souvent ce qu’aucun message ne débloque. Dites-le-nous, et nous laisserons la place.',
            'button' => 'C’est noté, j’appelle',
            'done' => 'C’est noté. Nous n’enverrons rien de plus pour l’instant.',
        ],

        'offer_phone_option' => [
            'title' => 'L’enregistrement par téléphone',
            'body' => 'Un membre de notre équipe appelle votre proche et l’enregistre pendant la conversation. Rien à manipuler de son côté.',
            'button' => 'Demander cette option',
            'done' => 'C’est demandé. Nous vous rappelons sous 48 heures pour organiser les appels.',
            'unavailable' => 'Cette option n’est pas disponible pour le moment. Écrivez-nous et nous verrons ensemble.',
        ],

        'react_heart' => [
            'title' => 'Envoyer un cœur',
            'body' => 'Un cœur sur « :title ». C’est ce qui donne envie de raconter la suivante.',
            'button' => 'Envoyer un cœur',
            'done' => 'C’est envoyé.',
            'no_story' => 'Aucune histoire partagée pour l’instant.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | L'espace de l'Initiateur·rice
    |--------------------------------------------------------------------------
    |
    | Le vocabulaire de ces pages est celui de quelqu'un qui organise, pas de
    | quelqu'un qui surveille : « où en est » et non « a-t-il répondu ». Le
    | narrateur est souverain, y compris face à l'enfant qui a offert le
    | service, et les mots le disent avant que les règles ne l'appliquent.
    |
    */

    'days' => [
        '1' => 'Lundi',
        '2' => 'Mardi',
        '3' => 'Mercredi',
        '4' => 'Jeudi',
        '5' => 'Vendredi',
        '6' => 'Samedi',
        '7' => 'Dimanche',
    ],

    'nav' => [
        'label' => 'Les pages de votre espace',
        'skip' => 'Aller au contenu',
        'dashboard' => 'Le projet',
        'questions' => 'Les questions',
        'family' => 'Les proches',
        'book' => 'Le livre',
        'data' => 'Vos données',
        'settings' => 'Les réglages',
        'orders' => 'Ma commande',
    ],

    'no_project' => [
        'title' => 'Aucun projet pour l’instant',
        'body' => 'Dès que votre commande est confirmée, votre projet apparaît ici.',
        'cta' => 'Découvrir l’offre',
    ],

    'dashboard' => [
        'title' => 'Le projet de :name',
        'title_generic' => 'Votre projet',
        'next_prompt' => 'Prochaine question : :when',
        'next_prompt_none' => 'Aucune question programmée pour l’instant.',
        'paused_until' => 'Les questions sont en pause jusqu’au :date.',
        'cadence' => 'Rythme : :cadence',
        'timeline' => 'Les histoires',
        'timeline_empty' => 'Rien encore. La première question part bientôt.',
        'not_shared_yet' => 'Pas encore partagée',
        'private_notice' => 'Vous voyez où en est chaque histoire. Le texte et la voix n’apparaissent qu’après le partage, et c’est :name qui en décide.',
        'copy_link_hint' => 'Un message de votre part vaut mieux qu’un des nôtres. Le lien précédent devient inutilisable.',
        'listen' => 'Écouter comme un proche',
        'listen_hint' => 'Vous écoutez avec votre propre lien, comme les autres proches.',
        'listen_open' => 'Ouvrir ma page d’écoute',
        'alerts' => 'À votre attention',
        'pause' => 'Demander une pause',
        'this_week' => 'La question de cette semaine',
        'send_link' => 'Envoyer le lien à :name',
        'send_link_generic' => 'Envoyer le lien',
        'story_number' => 'Histoire :n',
        'recorded_on' => 'Enregistrée le :date',
        'shared_on' => 'Partagée le :date',
        'share' => [
            'title' => 'Le lien est prêt',
            'copy' => 'Copier le lien',
            'copied' => 'Copié',
            'whatsapp' => 'WhatsApp',
            'sms' => 'SMS',
            'hint' => 'Le lien précédent ne fonctionne plus.',
        ],
    ],

    'questions' => [
        'reordered' => 'L’ordre est enregistré.',
        'updated' => 'C’est enregistré.',
        'added' => 'Votre question est ajoutée.',
        'title' => 'Les questions posées à :name',
        'title_generic' => 'Les questions',
        'intro' => 'Vous choisissez l’ordre et vous pouvez écarter ce qui ne convient pas. :name garde toujours le droit de ne pas répondre.',
        'asked' => 'Déjà posée',
        'excluded' => 'Écartée',
        'exclude' => 'Écarter',
        'restore' => 'Remettre',
        'move_up' => 'Monter',
        'move_down' => 'Descendre',
        'queue_title' => 'Les prochaines questions',
        'queue_intro' => 'Dans cet ordre, une par envoi. Montez ce qui vous tient à cœur, écartez ce qui ne convient pas : c’est enregistré aussitôt.',
        'queue_empty' => 'Toutes les questions ont été posées. Ajoutez la vôtre ci-dessous.',
        'first' => 'Poser en premier',
        'position' => 'Question :n',
        'see_more' => 'Voir :count de plus',
        'see_less' => 'Réduire',
        'excluded_count' => 'Questions écartées (:count)',
        'asked_count' => 'Déjà posées (:count)',
        'add' => [
            'title' => 'Poser votre propre question',
            'label' => 'Votre question',
            'hint' => 'Elle sera posée telle quelle, à la place d’une question du corpus.',
            'submit' => 'Ajouter cette question',
            'waiting' => 'Un instant…',
            'counter' => ':count / :max',
        ],
    ],

    'family' => [
        'invited' => 'L’invitation est partie.',
        'link_reissued' => 'Voici un nouveau lien pour cette personne.',
        'removed' => 'Cette personne n’a plus accès.',
        'title' => 'Les proches qui écoutent',
        'intro' => 'Chaque personne a son propre lien. Retirer un accès ne retire que celui-là.',
        'empty' => 'Personne pour l’instant, à part vous.',
        'you' => 'Vous',
        'can_contribute' => 'Peut ajouter des photos et des souvenirs',
        'invited_at' => 'Invité·e le :date',
        'first_seen_at' => 'A ouvert son lien le :date',
        'never_opened' => 'N’a pas encore ouvert son lien',
        'reissue' => 'Réémettre le lien',
        'reissue_hint' => 'Le lien précédent cesse de fonctionner.',
        'remove' => 'Retirer l’accès',
        'status_opened' => 'Lien ouvert',
        'status_pending' => 'Pas encore ouvert',
        'link_title' => 'Le nouveau lien de :name',
        'remove_confirm' => [
            'title' => 'Retirer l’accès de :name ?',
            'body' => 'Son lien cessera de fonctionner immédiatement. Vous pourrez l’inviter de nouveau plus tard.',
            'confirm' => 'Retirer l’accès',
        ],
        'invite' => [
            'title' => 'Inviter un proche',
            'intro' => 'La personne reçoit un lien à elle, sans compte ni mot de passe.',
            'waiting' => 'Un instant…',
            'name' => 'Son prénom',
            'relationship' => 'Son lien de parenté',
            'email' => 'Son courriel',
            'phone' => 'Son numéro de téléphone',
            'contact_hint' => 'Un courriel ou un numéro suffit.',
            'can_contribute' => 'L’autoriser à ajouter des photos et des souvenirs',
            'submit' => 'Envoyer l’invitation',
        ],
    ],

    'settings' => [
        'saved' => 'Vos réglages sont enregistrés.',
        'lexicon_added' => 'Le mot est ajouté au lexique.',
        'lexicon_removed' => 'Le mot est retiré du lexique.',
        'paused' => 'C’est noté : aucune question pendant :weeks semaines.',
        'title' => 'Les réglages du projet',
        'rhythm' => 'Le rythme',
        'cadence' => 'Fréquence des questions',
        'day' => 'Jour d’envoi',
        'slot' => 'Moment de la journée',
        'address_form' => 'Forme d’adresse',
        'timezone' => 'Fuseau horaire : :timezone',
        'next_prompt' => 'Prochain envoi : :when',
        'submit' => 'Enregistrer',
        'saved_short' => 'Enregistré',
        'waiting' => 'Un instant…',
        'lexicon' => [
            'title' => 'Le lexique',
            'intro' => 'Les noms propres de votre famille : le village, les surnoms, l’orthographe exacte. C’est vous qui les connaissez, pas :name, et pas nous.',
            'term' => 'Ce qui est entendu',
            'replacement' => 'Ce qu’il faut écrire',
            'notes' => 'Une précision (facultatif)',
            'submit' => 'Ajouter au lexique',
            'remove' => 'Retirer',
            'empty' => 'Le lexique est vide.',
            'heard' => 'Entendu « :term »',
        ],
        'pause' => [
            'title' => 'Mettre les questions en pause',
            'intro' => 'Une pause a toujours un terme, et :name en est prévenu·e.',
            'weeks' => 'Combien de semaines ?',
            'fewer' => 'Une semaine de moins',
            'more' => 'Une semaine de plus',
            'submit' => 'Mettre en pause',
        ],
        'mandate' => [
            'title' => 'Valider à la place de :name',
            'body' => 'Cette possibilité existe pour les situations où :name ne peut plus valider ses histoires. Elle demande son accord explicite, et elle cesse dès qu’il ou elle le retire.',
            'submit' => 'En savoir plus',
        ],
    ],

    'orders' => [
        'top_up_title' => 'Compléter ma commande',
        'top_up_body' => 'Vous pouvez encore ajouter ceci. Le reste de votre commande ne change pas.',
        'top_up_add' => 'Ajouter — :price',
        'top_up_unavailable' => 'Cette option n’est plus disponible. Écrivez-nous si vous pensez qu’il s’agit d’une erreur.',
        'top_up_done' => 'C’est ajouté à votre commande.',
        'top_up_sku_phone_option' => 'L’enregistrement par téléphone',
        'top_up_sku_phone_option_hint' => 'Un membre de l’équipe appelle chaque semaine, une quinzaine de minutes, et pose la question à votre place.',
        'top_up_sku_ebook' => 'Le livre numérique',
        'top_up_sku_ebook_hint' => 'La version numérique du livre, en plus de l’exemplaire relié.',
        'withdrawal_requested' => 'Votre demande est enregistrée. Nous vous répondons sous 48 heures.',
        // Ni refus sec ni silence : on explique la garantie et on donne le
        // contact. Le refus sec est l'occasion parfaite de perdre une famille
        // qu'on aurait pu garder.
        'withdrawal_closed' => 'Le délai de rétractation de quatorze jours est passé. Notre garantie « satisfait ou remboursé » de trente jours peut s’appliquer : écrivez-nous et nous regarderons ensemble.',
        'title' => 'Ma commande',
        'empty' => 'Aucune commande pour l’instant.',
        'paid_at' => 'Payée le :date',
        'total' => 'Total : :amount',
        'refunded' => 'Remboursé : :amount',
        'invoice' => 'Voir la facture',
        'items' => 'Le détail',
        'withdrawal' => 'Exercer mon droit de rétractation',
        'withdrawal_until' => 'Vous pouvez vous rétracter jusqu’au :date, sans avoir à vous justifier.',
        'withdraw_confirm' => [
            'title' => 'Exercer votre droit de rétractation ?',
            'body' => 'Nous vous répondons sous 48 heures et le remboursement suit. Si votre proche a déjà commencé à enregistrer, nous en tenons compte avec vous.',
            'confirm' => 'Je me rétracte',
        ],
        'support' => 'Écrire au support',
        'withdrawal_expired' => 'Le délai de quatorze jours est passé. Si la personne que vous avez invitée préfère ne pas participer, nous vous remboursons intégralement dans les trente jours : écrivez-nous à :email.',
        'phone_option' => 'Enregistrement par téléphone',
        'phone_option_slot' => 'Appel prévu le jour :day, :slot',
    ],

    /*
     * Le livre (bloc 13).
     *
     * Le mot qui gouverne toute la page : « la matière ». R-6 interdit un
     * seuil en nombre d'histoires, et la jauge doit le faire comprendre sans
     * l'expliquer — quatre mesures visibles valent mieux qu'un pourcentage
     * unique qui laisserait croire qu'il suffit d'en enregistrer une de plus.
     *
     * Aucun délai d'impression n'est annoncé : le devis n'est pas fait
     * (doc 03 P0-14).
     */
    'book' => [
        'eyebrow' => 'Le livre',
        'title' => 'Le livre de :first_name',
        'intro' => 'Le livre se déclenche quand la matière suffit, pas à un nombre d’histoires. Voici où vous en êtes.',

        'gauge' => [
            'title' => 'La matière recueillie',
            'words' => 'Mots',
            'audio' => 'Minutes de voix',
            'pages' => 'Pages estimées',
            'themes' => 'Thèmes abordés',
            'ready' => 'Il y a de quoi faire un livre.',
            'not_ready' => 'Il manque encore de la matière — et ce que vous avez déjà ne se perd pas.',
            // Le verrou réel n'est pas celui des mots : à 280 mots la page,
            // les 12 000 mots du référentiel ne font que 48 pages.
            'pages_hint' => 'C’est le nombre de pages qui décide en dernier : un texte dense fait moins de pages qu’on ne croit, et les photos en ajoutent.',
        ],

        'format' => [
            'title' => 'La forme proposée',
            'current' => 'Forme retenue',
            'proposed' => 'Forme proposée',
            'help' => 'Nous proposons la forme que la matière permet. Rien ne vous oblige à la suivre, et rien ne presse.',
        ],

        'chapters' => [
            'title' => 'Les chapitres',
            'help' => 'Toutes les histoires validées, dans l’ordre où elles ont été racontées. Décochez ce que vous ne voulez pas imprimer, et remontez ce qui doit ouvrir le livre.',
            'empty' => 'Aucune histoire validée pour l’instant.',
            'include' => 'Inclure dans le livre',
            'move_up' => 'Remonter',
            'move_down' => 'Descendre',
            'to_top' => 'Mettre en premier',
            'words' => ':count mots',
            'photos' => ':count photo|:count photos',
            'locked' => 'La sélection est arrêtée : le livre est parti à l’impression.',
            'qr_revoke' => 'Désactiver le code de cette histoire',
            'qr_restore' => 'Réactiver le code de cette histoire',
        ],

        'foreword' => [
            'title' => 'Votre avant-propos',
            'help' => 'Quelques lignes en ouverture, si vous le souhaitez. Facultatif.',
            'label' => 'Avant-propos',
        ],

        'lexicon' => [
            'title' => 'Les noms propres',
            'help' => 'La transcription se trompe souvent sur les noms de lieux et de personnes. Vérifiez cette liste avant d’imprimer : une faute sur un nom est celle qui se remarque.',
            'none' => 'Aucun nom à vérifier.',
            'add' => 'Ajouter au lexique',
        ],

        'proof' => [
            'title' => 'Le bon à tirer',
            'help' => 'Le bon à tirer est le livre tel qu’il sera imprimé. Relisez-le tranquillement.',
            'generate' => 'Générer le bon à tirer',
            'regenerate' => 'Regénérer le bon à tirer',
            'open' => 'Ouvrir le bon à tirer',
            'version' => 'Version :number, générée le :date',
            'pages' => ':count pages',
            'pending' => 'Le bon à tirer est en cours de fabrication. Vous recevrez un message quand il sera prêt — quelques minutes suffisent en général.',
            'none' => 'Aucun bon à tirer pour l’instant.',
        ],

        'approve' => [
            'title' => 'Approuver et commander',
            'final_print' => 'Je comprends que l’imprimé est définitif : une fois le livre imprimé, plus rien ne peut être corrigé.',
            'lexicon_reviewed' => 'J’ai relu les noms propres et les dates.',
            'submit' => 'Approuver et commander',
            'waiting' => 'Un instant…',
            'help' => 'Nous ne vous annonçons pas de délai tant que l’imprimeur n’est pas choisi. Vous serez prévenu à chaque étape.',
        ],

        'tracking' => [
            'title' => 'Où en est votre livre',
            'approved' => 'Approuvé le :date',
            'ordered' => 'Commandé le :date',
            'printed' => 'Imprimé le :date',
            'delivered' => 'Livré le :date',
            'extra_copies' => 'Exemplaires supplémentaires',
            'extra_copies_price' => ':price € l’exemplaire, cinq au maximum en une fois.',
            'order_copies' => 'Commander',
            'defect' => 'Signaler un défaut d’impression',
            'defect_help' => 'Un livre abîmé, mal massicoté, aux pages inversées : nous le réimprimons sans condition.',
        ],

        /*
         * Le code du livre (doc 04 §7). Le texte doit faire comprendre deux
         * choses en trois lignes : c'est facultatif, et cela se décide une
         * fois pour tous les exemplaires.
         */
        'code' => [
            'title' => 'Protéger l’écoute par un code',
            'help' => 'Par défaut, les codes imprimés dans le livre s’ouvrent sans rien demander : c’est ce qui permet de prêter le livre. Vous pouvez ajouter un code, à écrire sur le rabat ou à donner de vive voix — il sera demandé une fois, puis retenu un mois sur l’appareil.',
            'label' => 'Le code, au moins quatre caractères',
            'submit' => 'Poser ce code',
            'change' => 'Changer le code',
            'remove' => 'Retirer le code',
            'is_set' => 'Un code protège l’écoute. Nous ne pouvons pas vous le rappeler : il est chiffré, comme un mot de passe.',
            'saved' => 'Le code est posé. Il sera demandé au prochain scan.',
            'removed' => 'Le code est retiré : les codes du livre s’ouvrent de nouveau sans rien demander.',
        ],

        'saved' => 'C’est enregistré.',
        'rendering' => 'Le bon à tirer est en cours de fabrication. Vous recevrez un message quand il sera prêt.',
        'ordered' => 'Votre livre est commandé. Nous vous tenons au courant à chaque étape.',
        'no_chapter' => 'Il faut au moins un chapitre pour fabriquer un bon à tirer.',
    ],

    /*
     * « Mes données » (bloc 14).
     *
     * Le ton dit la non-captivité mieux qu'une promesse : ces fichiers sont à
     * la famille, elle n'a pas à nous remercier de les lui rendre, et
     * l'effacement s'explique sans être découragé.
     */
    'data' => [
        'eyebrow' => 'Vos données',
        'title' => 'Vos données vous appartiennent',
        'intro' => 'Vous pouvez récupérer l’intégralité de ce que vous avez enregistré, à tout moment et sans frais. Les fichiers s’ouvrent avec les logiciels que vous avez déjà : nous ne sommes pas nécessaires pour les lire.',

        'export' => [
            'title' => 'Télécharger mes données',
            'help' => 'Nous préparons un dossier complet — les voix, les textes, les photos, le livre s’il existe. Vous recevrez un courriel dès qu’il est prêt : cela prend quelques minutes.',
            'full' => 'Tout ce que j’ai enregistré',
            'offline' => 'Tout, avec un lecteur qui fonctionne sans connexion',
            'gdpr' => 'Tout, plus mes consentements et le journal de mes données',
            'submit' => 'Préparer mon dossier',
            'waiting' => 'Un instant…',
            'history' => 'Vos derniers dossiers',
            'ready' => 'Prêt, valable jusqu’au :date',
            'building' => 'En préparation',
            'expired' => 'Lien expiré — vous pouvez en demander un nouveau',
            'size' => ':size Mo',
        ],

        'erasure' => [
            'title' => 'Effacer mon projet',
            'help' => 'L’effacement supprime définitivement les enregistrements, les textes et les photos. Il est irréversible : nous ne pourrons rien récupérer, même à votre demande.',
            'kept' => 'Ce que nous conservons malgré tout : les factures, pour la comptabilité, et la preuve des consentements donnés — sans votre nom. La loi nous y oblige.',
            'narrator_first' => 'Si le narrateur demande lui-même l’effacement, sa demande passe avant la vôtre : ce sont ses récits.',
            'delay' => 'Nous vous confirmons l’effacement sous trente jours au plus, et le plus souvent le jour même.',
            'blocked' => 'Un livre de ce projet est en cours d’impression. L’effacement aura lieu dès sa livraison, ou tout de suite si vous annulez la commande — écrivez-nous.',
            'requested' => 'Votre demande d’effacement est enregistrée. Nous revenons vers vous très vite.',
            'confirm_label' => 'Pour confirmer, tapez EFFACER',
            'submit' => 'Demander l’effacement',
        ],

        'export_queued' => 'Votre dossier est en préparation. Vous recevrez un courriel dès qu’il est prêt.',
        'erasure_requested' => 'Votre demande est enregistrée. Nous vous répondons sous trente jours au plus.',
    ],
];
