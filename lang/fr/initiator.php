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
        'dashboard' => 'Le projet',
        'questions' => 'Les questions',
        'family' => 'Les proches',
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
        'copy_link' => 'Copier le lien de cette semaine',
        'copy_link_hint' => 'Un message de votre part vaut mieux qu’un des nôtres. Le lien précédent devient inutilisable.',
        'send_whatsapp' => 'Envoyer par WhatsApp',
        'copied' => 'Lien prêt à coller',
        'listen' => 'Écouter comme un proche',
        'listen_hint' => 'Vous écoutez avec votre propre lien, comme les autres proches.',
        'alerts' => 'À votre attention',
        'pause' => 'Demander une pause',
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
        'save_order' => 'Enregistrer l’ordre',
        'add' => [
            'title' => 'Poser votre propre question',
            'label' => 'Votre question',
            'hint' => 'Elle sera posée telle quelle, à la place d’une question du corpus.',
            'submit' => 'Ajouter cette question',
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
        'invite' => [
            'title' => 'Inviter un proche',
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
        'lexicon' => [
            'title' => 'Le lexique',
            'intro' => 'Les noms propres de votre famille : le village, les surnoms, l’orthographe exacte. C’est vous qui les connaissez, pas :name — et pas nous.',
            'term' => 'Ce qui est entendu',
            'replacement' => 'Ce qu’il faut écrire',
            'notes' => 'Une précision (facultatif)',
            'submit' => 'Ajouter au lexique',
            'remove' => 'Retirer',
            'empty' => 'Le lexique est vide.',
        ],
        'pause' => [
            'title' => 'Mettre les questions en pause',
            'intro' => 'Une pause a toujours un terme, et :name en est prévenu·e.',
            'weeks' => 'Combien de semaines ?',
            'submit' => 'Mettre en pause',
        ],
        'mandate' => [
            'title' => 'Valider à la place de :name',
            'body' => 'Cette possibilité existe pour les situations où :name ne peut plus valider ses histoires. Elle demande son accord explicite, et elle cesse dès qu’il ou elle le retire.',
            'submit' => 'En savoir plus',
        ],
    ],

    'orders' => [
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
        'withdrawal_expired' => 'Le délai de quatorze jours est passé. Si la personne que vous avez invitée préfère ne pas participer, nous vous remboursons intégralement dans les trente jours : écrivez-nous à :email.',
        'phone_option' => 'Enregistrement par téléphone',
        'phone_option_slot' => 'Appel prévu le jour :day, :slot',
    ],
];
