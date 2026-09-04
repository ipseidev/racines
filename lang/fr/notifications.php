<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Messages envoyés par SMS et par courriel
|--------------------------------------------------------------------------
|
| Trois règles d'anti-hameçonnage (doc 04 §9) : la marque est nommée, la durée
| de validité est annoncée, et le message dit de ne communiquer le code à
| personne. Un code n'arrive jamais avec un lien à cliquer.
|
*/

return [

    'prompt' => [
        'subject' => 'Votre question de la semaine',
        'greeting' => 'Bonjour :name,',
        // Le lien est en dernier : c'est la seule position où un client SMS
        // ne le tronque pas, et la seule où la phrase a été lue avant que le
        // doigt touche l'écran.
        'sms' => ':name, votre question de la semaine de :brand vous attend : :link',
        'button' => 'Répondre en parlant',
        'no_password' => 'Cette page ne vous demandera jamais de mot de passe ni de paiement.',
        'help' => 'Une question ? Écrivez-nous à :email.',
        'signature' => 'À bientôt, l’équipe :brand.',
    ],

    /*
     * Relecture (bloc 07). Aucun compte à rebours, aucune date butoir : le
     * dossier est formel, l'absence de réaction ne vaut jamais accord.
     */
    'review' => [
        'greeting' => 'Bonjour :name,',
        'button' => 'Relire mon histoire',
        'no_deadline' => 'Prenez le temps qu’il vous faut : rien ne se décide sans vous.',
        'ready' => [
            'subject' => 'Votre histoire est prête à relire',
            'line' => 'Nous avons mis votre récit au propre. Relisez-le, corrigez-le si vous voulez, puis dites-nous ce que vous souhaitez en faire.',
            'sms' => ':name, votre histoire est prête à relire chez :brand : :link',
        ],
        'decide_later' => [
            'subject' => 'Votre histoire vous attend',
            'line' => 'Vous nous aviez demandé de vous redemander plus tard ce que vous souhaitiez faire de cette histoire. La voici, mise au propre.',
            'sms' => ':name, comme convenu, votre histoire vous attend chez :brand : :link',
        ],
    ],

    /*
     * Invitation d'un proche (bloc 08). Une invitation à écouter la voix d'un
     * aïeul est exactement ce qu'un hameçonneur imiterait : la marque est
     * nommée, le lien part du domaine annoncé, et le message dit qu'aucune
     * page ne demandera de mot de passe ni de paiement.
     */
    'family_invitation' => [
        'subject' => 'Écoutez les histoires de :narrator',
        'greeting' => 'Bonjour :name,',
        'line' => ':inviter vous invite à écouter les histoires que :narrator enregistre.',
        'button' => 'Écouter les histoires',
        // La seule protection contre la circulation du lien dans un groupe de
        // messagerie, et elle vaut mieux qu'une mention en petits caractères.
        'personal' => 'Ce lien est personnel : il vous identifie. Ne le transmettez qu’à des proches, et jamais dans un groupe public.',
        'sms' => ':inviter vous invite à écouter les histoires de :narrator sur :brand : :link',
        'your_relative' => 'votre proche',
    ],

    /*
     * Une réaction reçue (bloc 08). Le message **nomme** la personne et cite
     * son mot : « une réaction » ne fait rien ressentir, « Marie vous dit
     * merci » si. Et il ne porte aucun lien — on rapporte une bonne
     * nouvelle, on ne donne pas une tâche.
     */
    'reaction_received' => [
        'subject' => ':names a écouté votre histoire',
        'greeting' => 'Bonjour :name,',
        'line' => ':names a écouté « :title ».',
        'comment' => ':name vous écrit : « :comment »',
        'sms' => ':names a écouté « :title » sur :brand.',
        'untitled' => 'votre histoire',
        'digest' => [
            'subject' => 'On vous a écouté·e hier',
            'line' => 'Hier, une personne a écouté vos histoires.|Hier, :count personnes ont écouté vos histoires.',
            'story' => '« :title » : :names',
        ],
    ],

    /*
     * Moteur de complétion (bloc 09, annexe C).
     *
     * Le ton est la partie la plus délicate du produit. Trois règles, tenues
     * par un test (`ForbiddenVocabularyTest`) :
     *
     *  — jamais « vous n'avez pas », jamais « toujours pas », jamais
     *    « dernier rappel » : le narrateur n'a rien promis, il raconte quand
     *    il veut ;
     *  — la porte reste ouverte, sans échéance : « quand vous voudrez »,
     *    « votre histoire vous attend » ;
     *  — l'Initiateur·rice n'est jamais mise en cause non plus : on lui
     *    propose un geste, on ne lui reproche pas un silence.
     */
    'engine' => [
        'greeting' => 'Bonjour :name,',

        // J+7 : le narrateur n'a pas encore accepté l'invitation.
        'invitation_reminder' => [
            'subject' => 'Votre livre de souvenirs vous attend',
            'line' => ':inviter aimerait recueillir vos souvenirs. Il n’y a rien à installer, et vous répondez en parlant, quand vous voulez.',
            'sms' => ':name, :inviter aimerait recueillir vos souvenirs sur :brand. Quand vous voulez : :link',
            'button' => 'Découvrir',
        ],

        // J+14 : on en parle à l'Initiateur·rice, avec un geste à faire.
        'invitation_alert' => [
            'subject' => 'Un coup de main pour :narrator ?',
            'line' => 'L’invitation de :narrator n’a pas encore été ouverte. Cela arrive souvent : un SMS d’un numéro inconnu se remarque moins qu’un message de vous.',
            'sms' => ':name, un message de vous aiderait :narrator à démarrer : :link',
            'button' => 'Renvoyer moi-même le lien',
            'audio_hint' => 'Un message vocal de trente secondes fonctionne mieux qu’un texte : votre voix se reconnaît.',
        ],

        // J+3 : le lien de la question n'a jamais été ouvert.
        'link_resend' => [
            'subject' => 'Votre question de la semaine',
            'line' => 'Voici de nouveau votre question, au cas où le message précédent se serait perdu.',
            'sms' => ':name, votre question de la semaine vous attend chez :brand : :link',
            'button' => 'Répondre en parlant',
        ],

        // J+2 : un enregistrement commencé et jamais envoyé.
        'draft_waiting' => [
            'subject' => 'Votre histoire vous attend',
            'line' => 'Vous avez commencé à raconter, et votre enregistrement est resté sur votre téléphone. Il est toujours là : reprenez quand vous voulez.',
            'sms' => ':name, votre enregistrement est resté sur votre téléphone. Il vous attend : :link',
            'button' => 'Reprendre mon enregistrement',
        ],

        // J+4 : une histoire transcrite attend une décision.
        'validation_reminder' => [
            'subject' => 'Votre histoire est prête à relire',
            'line' => 'Le texte de votre histoire est prêt. Relisez-le quand vous voulez, puis dites-nous ce que vous souhaitez en faire.',
            'sms' => ':name, votre histoire est prête à relire chez :brand : :link',
            'button' => 'Relire mon histoire',
        ],

        // J+5 : une histoire partagée que personne n'a écoutée.
        'new_story_nudge' => [
            'subject' => ':narrator a partagé une nouvelle histoire',
            'line' => ':narrator a raconté « :title ». Deux minutes d’écoute, et un mot de vous lui feront plaisir.',
            'sms' => ':narrator a partagé une nouvelle histoire sur :brand : :link',
            'button' => 'Écouter',
        ],

        // Trois histoires partagées sans une seule réaction.
        'react_suggestion' => [
            'subject' => ':narrator raconte, et personne ne répond',
            'line' => ':narrator a partagé :count histoires. Un cœur suffit : c’est ce qui donne envie de raconter la suivante.',
            'sms' => ':name, un cœur sur la dernière histoire de :narrator suffirait : :link',
            'button' => 'Envoyer un cœur',
        ],

        // J+10 de silence : une question plus légère.
        'lighter_question' => [
            'subject' => 'Une question plus légère',
            'line' => 'En voici une plus simple, pour le plaisir. Une minute suffit, et vous pouvez tout aussi bien la laisser de côté.',
            'sms' => ':name, une question plus légère vous attend chez :brand : :link',
            'button' => 'Répondre en parlant',
        ],

        // J+21 de silence : on en parle à l'Initiateur·rice, quatre gestes.
        'initiator_alert' => [
            'subject' => 'Des nouvelles de :narrator ?',
            'line' => ':narrator n’a pas enregistré depuis trois semaines. Ce n’est pas grave, et ça se débloque souvent d’un coup de fil. Voici ce que vous pouvez faire.',
            'sms' => ':name, un coup de fil à :narrator débloquerait peut-être les choses : :link',
            'button' => 'Renvoyer le lien moi-même',
            'switch' => 'Passer à une question toutes les deux semaines',
            'call' => 'J’appelle :narrator moi-même',
            'phone' => 'Proposer l’enregistrement par téléphone',
        ],

        // Une pause demandée, confirmée avec sa date de reprise.
        'pause_confirmed' => [
            'subject' => 'C’est noté : pause jusqu’au :date',
            'line' => 'Vous ne recevrez aucune question jusqu’au :date. Nous reprendrons tranquillement à cette date, et vous pourrez toujours écrire avant si vous en avez envie.',
            'sms' => ':name, c’est noté : aucune question jusqu’au :date. Nous reprendrons à cette date.',
        ],

        // À l'échéance de la pause.
        'resume' => [
            'subject' => 'On reprend quand vous voulez',
            'line' => 'Votre pause se termine. Voici une question, sans obligation : elle vous attendra le temps qu’il faudra.',
            'sms' => ':name, votre pause se termine. Une question vous attend chez :brand : :link',
            'button' => 'Répondre en parlant',
        ],

        // Un rythme qui ralentit : réduire vaut mieux qu'arrêter.
        'slower_rhythm_offer' => [
            'subject' => 'Une question toutes les deux semaines ?',
            'line' => 'Une question par semaine, c’est peut-être beaucoup. Une toutes les deux semaines laisse plus de place, et le livre se construit tout aussi bien.',
            'sms' => ':name, préférez-vous une question toutes les deux semaines ? :link',
            'button' => 'Oui, toutes les deux semaines',
        ],
    ],

    /*
     * L'invitation-cadeau (bloc 10). Le message le plus délicat du produit :
     * il arrive sans être attendu, d'un expéditeur inconnu, et propose de
     * raconter sa vie. Trois choses le rendent crédible — le nom de la
     * personne qui offre, son message personnel, et la phrase qui dit
     * qu'aucune page ne demandera de mot de passe ni de paiement.
     *
     * Il ne demande **rien** : juste de découvrir de quoi il s'agit.
     */
    'gift_invitation' => [
        'subject' => ':inviter vous offre un livre de vos souvenirs',
        'greeting' => 'Bonjour :name,',
        'line' => ':inviter aimerait recueillir vos souvenirs et en faire un livre, avec :brand. Vous répondez en parlant, quand vous voulez, et vous décidez de tout.',
        'button' => 'Découvrir, sans engagement',
        'no_obligation' => 'Rien ne commence avant que vous ayez dit oui. Et vous pouvez dire non : c’est prévu, et c’est respecté.',
        'sms' => ':name, :inviter vous offre un livre de vos souvenirs avec :brand. Pour découvrir : :link. Ce lien ne demandera jamais de mot de passe ni de paiement.',
    ],

    'checkout' => [
        'confirmation' => [
            'subject' => 'Votre commande est confirmée',
            'greeting' => 'Bonjour :name,',
            'line' => 'Merci. Tout est prêt pour recueillir les souvenirs de :narrator.',
            'gift_date' => 'L’invitation lui sera envoyée le :date, à neuf heures.',
            // Dit maintenant plutôt qu'au moment du refus : la déception ne
            // doit pas se doubler d'une surprise.
            'free_to_refuse' => 'Il reste entièrement libre d’accepter ou non. S’il préfère ne pas participer, nous vous remboursons intégralement.',
            'withdrawal' => 'Vous disposez d’un droit de rétractation jusqu’au :date. Vous pouvez l’exercer depuis votre espace, sans justification.',
        ],
    ],

    /*
     * Le code de réduction de bienvenue (T-141). Il dit le code, sa valeur,
     * sa date de fin et comment s'en servir : un courriel demandé pour un
     * code donne le code, pas un argumentaire.
     */
    'welcome_offer' => [
        'subject' => 'Vos :amount offerts sur le livre',
        'greeting' => 'Bonjour,',
        'code_line' => 'Voici votre code de réduction : :code',
        'value_line' => 'Il vous fait :amount de réduction sur toute votre commande, jusqu’au :date.',
        'how_line' => 'Entrez-le au récapitulatif de votre commande. Si vous commandez depuis l’appareil où vous l’avez demandé, il s’appliquera tout seul.',
        'button' => 'Commencer son livre',
        'news_line' => 'Vous avez aussi demandé nos nouvelles : elles seront rares, et un lien pour arrêter figure dans chaque message.',
    ],

    'initiator' => [
        'invitation_refused' => [
            'subject' => 'Des nouvelles de votre cadeau',
            'greeting' => 'Bonjour :name,',
            'line' => ':narrator a préféré ne pas participer pour le moment.',
            // On respecte à voix haute, et on ne suggère aucune relance.
            'respect' => 'C’est son choix, et nous le respectons. Cela arrive, et ce n’est pas un échec : proposer était déjà une attention.',
            'button' => 'Voir ma commande',
            'refund' => 'Vous êtes remboursé·e intégralement si vous le souhaitez. Il suffit de nous le dire depuis votre espace, sans justification.',
        ],
    ],

    'corpus_exhausted' => [
        'subject' => 'Toutes les questions ont été posées',
        'line' => 'Le corpus de questions est épuisé pour ce projet : plus aucune nouvelle question ne partira.',
        'action' => 'Vous pouvez ajouter vos propres questions depuis votre espace.',
    ],

    'new_link_requested' => [
        'support_subject' => 'Un narrateur demande un nouveau lien',
        'initiator_subject' => 'Votre proche demande un nouveau lien',
        'line' => 'Le lien d’enregistrement de l’histoire :story n’était plus valable.',
        'support_action' => 'Un nouveau lien a été émis et renvoyé sur son canal habituel.',
        'initiator_action' => 'Nous lui avons renvoyé un lien. Vous n’avez rien à faire.',
    ],

    'otp' => [
        'subject' => 'Votre code : :code',
        'greeting' => 'Bonjour,',
        'code_line' => 'Votre code est :code.',
        'expiry_line' => 'Il expire dans :minutes minutes.',
        'warning_line' => 'Ne le communiquez à personne, même à quelqu’un qui dirait appeler de notre part.',
        'sms' => ':brand : votre code est :code. Il expire dans :minutes minutes. Ne le communiquez à personne.',
    ],

    /*
     * Interne, adressé au support et à lui seul : le narrateur a parlé,
     * l'audio est en sécurité, et le texte n'arrive pas. Pour la famille cela
     * ressemble à un silence inexpliqué, et il faut reprendre à la main avant
     * que quiconque s'en aperçoive.
     */
    'transcription_failed' => [
        'subject' => 'Transcription en échec, reprise manuelle nécessaire',
        'line' => 'L’enregistrement :recording n’a pas pu être transcrit après plusieurs tentatives. L’audio d’origine est intact.',
        'action' => 'Relancer la transcription depuis l’administration, ou basculer le fournisseur de secours. Prévenir la famille si l’attente dépasse quarante-huit heures.',
    ],
];
