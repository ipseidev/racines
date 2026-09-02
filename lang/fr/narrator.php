<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Espace narrateur·rice
|--------------------------------------------------------------------------
|
| Langage simple, phrases courtes, aucune formulation technique. Une erreur
| dit toujours quoi faire ensuite (convention §16). Le vouvoiement est le
| réglage par défaut du projet.
|
*/

return [

    'link_unavailable' => [
        'not_found' => [
            'title' => 'Ce lien ne fonctionne pas',
            'body' => 'Le lien est peut-être incomplet. Vérifiez que vous l’avez ouvert en entier, depuis le message que vous avez reçu.',
        ],
        'expired' => [
            'title' => 'Ce lien a expiré',
            'body' => 'Les liens ne restent valables qu’un temps, pour votre sécurité. Vous pouvez en demander un nouveau.',
        ],
        'revoked' => [
            'title' => 'Ce lien n’est plus valable',
            'body' => 'Il a été remplacé, ou votre histoire est déjà enregistrée. Vous pouvez demander un nouveau lien.',
        ],
        'used' => [
            'title' => 'Ce lien a déjà servi',
            'body' => 'Il ne fonctionnait qu’une fois. Si vous avez encore quelque chose à faire, demandez un nouveau lien.',
        ],
        'type_mismatch' => [
            'title' => 'Ce lien ne mène pas ici',
            'body' => 'Il correspond à une autre page. Ouvrez le lien depuis le message que vous avez reçu.',
        ],
        'request_new_link' => 'Demander un nouveau lien',
        'request_sent' => 'C’est noté. Nous vous renvoyons un lien très vite.',
        'help' => 'Besoin d’aide ? Écrivez-nous à :email.',
    ],

    'otp' => [
        'title' => 'Votre code de confirmation',
        'intro' => 'Nous avons envoyé un code à 6 chiffres au :destination.',
        'intro_no_code' => 'Pour continuer, nous devons vous envoyer un code à 6 chiffres.',
        'code_label' => 'Code à 6 chiffres',
        'submit' => 'Valider',
        'send' => 'Envoyer le code',
        'resend' => 'Renvoyer le code',
        'sent' => 'Code envoyé. Il arrive dans quelques secondes.',
        'invalid' => 'Ce code ne correspond pas. Vérifiez les six chiffres et réessayez.',
        'expired' => 'Ce code n’est plus valable. Demandez-en un nouveau.',
        'locked' => 'Trop d’essais. Patientez quinze minutes, puis demandez un nouveau code.',
        'warning' => 'Ne communiquez ce code à personne, même à quelqu’un qui dirait appeler de notre part.',
    ],

    'record' => [
        // Écran 1 — explication. Elle précède toujours la demande de micro :
        // une autorisation qui surgit sans prévenir se refuse par réflexe.
        'greeting' => ':name, voici votre question de la semaine',
        'greeting_tu' => ':name, voici ta question de la semaine',
        'mic_notice' => 'Quand vous appuierez sur le bouton, votre téléphone demandera l’autorisation d’utiliser le micro. Choisissez « Autoriser ».',
        'mic_notice_tu' => 'Quand tu appuieras sur le bouton, ton téléphone demandera l’autorisation d’utiliser le micro. Choisis « Autoriser ».',
        'ready' => 'Je suis prêt·e',

        // Écran 2 — permission.
        'requesting' => 'Votre téléphone va vous demander l’autorisation. Choisissez « Autoriser ».',

        // Écran 3 — enregistrement.
        'start' => 'Commencer',
        'pause' => 'Pause',
        'resume' => 'Reprendre',
        'finish' => 'Terminer',
        'recording' => 'Enregistrement en cours',
        'paused' => 'En pause',
        'elapsed' => 'Durée : :time',
        'soft_warning' => 'Vous parlez depuis dix minutes. Prenez tout votre temps, l’enregistrement s’arrêtera de lui-même à vingt minutes.',
        'hard_stop' => 'L’enregistrement s’est arrêté à vingt minutes. Ce que vous avez dit est conservé : vous pouvez l’envoyer.',
        'interrupted' => 'L’enregistrement s’est interrompu. Ce que vous avez dit est conservé.',
        'interrupted_resume' => 'Continuer mon histoire',
        'level_label' => 'Niveau du micro',

        // Écran 4 — vérification.
        'review_title' => 'Voulez-vous vous réécouter ?',
        'listen' => 'Réécouter',
        'send' => 'Envoyer',
        'restart' => 'Recommencer',
        'restart_confirm' => 'Recommencer effacera ce que vous venez d’enregistrer. Continuer ?',

        // Écran 5 — envoi.
        'uploading' => 'Envoi en cours',
        'uploading_notice' => 'Ne fermez pas cette page. Si cela arrive quand même, votre enregistrement est conservé sur votre téléphone.',
        'upload_failed_title' => 'L’envoi n’a pas abouti',
        'upload_failed_body' => 'Votre enregistrement est conservé sur votre téléphone. Vous pouvez réessayer.',
        'retry' => 'Réessayer',

        // Écran 6 — confirmation.
        'confirmed_title' => 'Votre histoire est enregistrée',
        'confirmed_body' => 'Merci :name.',

        // Brouillon retrouvé au chargement.
        'draft_title' => 'Vous avez un enregistrement en cours',
        'draft_body' => 'Nous avons retrouvé ce que vous avez commencé à raconter.',
        'draft_resume' => 'Reprendre mon enregistrement',
        'draft_discard' => 'Recommencer',
        'storage_low' => 'Il reste peu de place sur votre téléphone. L’enregistrement fonctionne, mais évitez les très longues réponses.',

        'written_link' => 'Répondre par écrit',
    ],

    'mic_help' => [
        'title' => 'Le micro n’est pas autorisé',
        'body' => 'Sans micro, nous ne pouvons pas enregistrer votre voix. Voici comment l’autoriser.',
        'retry' => 'Réessayer',
        'ios' => 'Sur iPhone : ouvrez Réglages, faites défiler jusqu’à Safari, touchez Micro, puis choisissez « Demander » ou « Autoriser ». Revenez ensuite sur cette page.',
        'android' => 'Sur Android : touchez le cadenas à gauche de l’adresse, en haut de l’écran, puis Autorisations, puis Micro, et choisissez « Autoriser ».',
        'samsung' => 'Sur Samsung Internet : touchez le cadenas à gauche de l’adresse, puis Autorisations, puis Micro, et choisissez « Autoriser ».',
        'other' => 'Cherchez l’icône de cadenas à côté de l’adresse de cette page, puis autorisez le micro.',
        'unsupported' => 'Votre navigateur ne sait pas enregistrer de son. Vous pouvez répondre par écrit, ou nous écrire pour qu’on vous appelle.',
    ],

    'written_answer' => [
        'title' => 'Répondre par écrit',
        'body' => 'Écrivez ce que vous auriez raconté. C’est aussi une histoire.',
        'label' => 'Votre réponse',
        'counter' => ':count caractères sur :max',
        'send' => 'Envoyer',
        'sent' => 'Merci, votre réponse est enregistrée.',
    ],

    'already_recorded' => [
        'title' => 'Vous avez déjà répondu à cette question',
        'title_with_date' => 'Vous avez déjà répondu à cette question le :date',
        'body' => 'Vous pouvez recommencer si vous préférez une autre version. Votre premier enregistrement est conservé.',
        'restart' => 'Recommencer',
        'close' => 'Fermer',
    ],

];
