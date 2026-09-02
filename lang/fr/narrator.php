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
        'already_sent' => 'Un code vous a déjà été envoyé. Utilisez celui-là : il est encore valable.',
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

    /*
     * Les trois choix de fin d'enregistrement (bloc 07).
     *
     * Toujours dans cet ordre, jamais présélectionnés, sans minuteur : le
     * dossier est formel, l'absence de réaction ne vaut jamais accord. Chaque
     * choix dit sa conséquence en une phrase, au présent, sans jargon.
     */
    'share_decision' => [
        'title' => 'Que souhaitez-vous faire de cette histoire ?',
        'body' => 'C’est votre récit. Vous décidez, et vous pourrez changer d’avis.',
        'share' => [
            'label' => 'Partager avec mes proches',
            'hint' => 'Vos proches pourront l’écouter et lire le texte.',
        ],
        'keep_private' => [
            'label' => 'Garder pour moi',
            'hint' => 'Personne d’autre que vous ne l’entendra.',
        ],
        'decide_later' => [
            'label' => 'Décider plus tard',
            'hint' => 'Nous vous le redemanderons, sans insister.',
        ],
        'recorded' => [
            'share' => 'C’est noté : vos proches pourront l’écouter dès que le texte sera prêt.',
            'keep_private' => 'C’est noté : cette histoire reste pour vous seul·e.',
            'decide_later' => 'C’est noté : nous vous le redemanderons plus tard.',
        ],
        'change' => 'Changer ma réponse',
    ],

    /*
     * Relecture (bloc 07). Le mot à mot n'est pas caché : c'est la parole de
     * la personne, et elle a le droit de vérifier ce que la machine en a fait.
     */
    'review' => [
        'title' => 'Votre histoire est prête',
        'body' => 'Relisez-la, corrigez-la si vous voulez, puis dites-nous ce que vous souhaitez en faire.',
        'listen' => 'Écouter votre enregistrement',
        'tab_fluide' => 'Texte mis au propre',
        'tab_verbatim' => 'Mot à mot',
        'edit' => 'Corriger le texte',
        'edit_label' => 'Votre texte',
        'edit_help' => 'Changez ce que vous voulez. Votre enregistrement, lui, ne bouge pas.',
        'save' => 'Enregistrer ma correction',
        'cancel' => 'Annuler',
        'saved' => 'Votre correction est enregistrée.',
        'empty' => 'Le texte ne peut pas être vide.',
        'no_audio' => 'L’enregistrement n’est pas encore disponible à l’écoute.',
        'visibility' => [
            'title' => 'Qui peut écouter ?',
            'all_family' => 'Tous mes proches',
            'choose' => 'Choisir qui peut écouter',
            'book_only' => 'Pour le livre seulement',
            'book_only_hint' => 'Elle sera imprimée, mais personne ne l’écoutera en ligne.',
        ],
        'keep_for_book' => 'Garder cette histoire pour le livre',
        'thanks' => [
            'share' => 'Merci. Vos proches peuvent maintenant écouter cette histoire.',
            'keep_private' => 'Merci. Cette histoire reste pour vous seul·e.',
            'decide_later' => 'Merci. Nous vous le redemanderons plus tard.',
        ],
    ],

    'thanks' => [
        'title' => 'Merci',
        'body' => 'Vous pouvez fermer cette page.',
    ],

    /*
     * Espace narrateur (bloc 07).
     *
     * Aucun nom d'état technique n'apparaît : un narrateur ne lit pas
     * « transcribed », il lit « en attente de votre choix ». Les libellés
     * disent où en est l'histoire *de son point de vue*, pas du point de vue
     * de la machine.
     */
    'space' => [
        'title' => 'Vos histoires',
        'empty' => 'Vous n’avez pas encore d’histoire enregistrée.',
        'request' => [
            'title' => 'Accéder à vos histoires',
            'body' => 'Indiquez le numéro de téléphone ou l’adresse e-mail sur lesquels vous recevez vos questions. Nous vous enverrons un code.',
            'label' => 'Numéro ou adresse e-mail',
            'send' => 'Recevoir un code',
            // La même phrase, que la coordonnée soit connue ou non : une
            // réponse différente ferait de cette page un annuaire.
            'sent' => 'Si nous vous connaissons, un code vient de partir. Il est valable quelques minutes.',
            'already_sent' => 'Un code vous a déjà été envoyé. Utilisez celui-là : il est encore valable.',
            'have_code' => 'J’ai déjà un code',
            'code_label' => 'Votre code',
            'verify' => 'Ouvrir mes histoires',
        ],
        'states' => [
            'recorded' => 'Enregistrée, en cours de transcription',
            'transcribed' => 'Gardée pour vous',
            'to_review' => 'En attente de votre choix',
            'validated' => 'Validée',
            'shared' => 'Partagée avec vos proches',
            'in_book' => 'Dans le livre',
            'hidden' => 'Masquée',
            'archived' => 'Archivée',
            'trashed' => 'Dans la corbeille',
            'deleted' => 'Supprimée',
        ],
        'restorable_until' => 'Récupérable jusqu’au :date',
        'pause' => 'Demander une pause',
        'pause_weeks' => 'Pendant combien de semaines ?',
        'paused' => 'C’est noté : aucune question pendant :weeks semaines.',
        'paused_until' => 'Vos questions sont en pause jusqu’au :date.',
    ],

    /*
     * Retraits (bloc 07). Cinq gestes, du plus doux au définitif. Chacun dit
     * ce qu'il fait et ce qu'il ne fait pas : « vous pourrez la remettre »
     * n'est pas une formule de politesse, c'est l'information qui permet
     * d'oser.
     */
    'withdrawals' => [
        'hide' => 'Masquer cette histoire',
        'hide_confirm' => 'Masquer cette histoire ? Vous pourrez la remettre plus tard.',
        'hidden' => 'Cette histoire est masquée. Vous pouvez la remettre quand vous voulez.',
        'unhide' => 'Remettre cette histoire',
        'unhidden' => 'Cette histoire est de nouveau visible.',
        'trash' => 'Mettre à la corbeille',
        'trash_confirm' => 'Mettre à la corbeille ? Vous aurez trente jours pour la récupérer.',
        'trashed' => 'Cette histoire est à la corbeille. Vous avez trente jours pour la récupérer.',
        'restore' => 'Récupérer cette histoire',
        'restored' => 'Cette histoire est récupérée.',
        'restore_window_closed' => 'Le délai de :days jours est passé : cette histoire ne peut plus être récupérée.',
        'delete' => 'Supprimer définitivement',
        'delete_confirm' => 'Cette suppression est définitive : l’enregistrement et le texte seront effacés, et nous ne pourrons pas les retrouver.',
        'delete_word' => 'SUPPRIMER',
        'delete_word_label' => 'Écrivez SUPPRIMER pour confirmer',
        'delete_word_missing' => 'Écrivez :word en majuscules pour confirmer la suppression.',
        'deleted' => 'Cette histoire est supprimée.',
        // Ce qui est imprimé est imprimé : le dire est la seule honnêteté
        // possible, et le taire serait promettre l'impossible.
        'printed_copies_warning' => 'Cette histoire figure dans un livre déjà imprimé. Nous la retirons de l’espace en ligne et des prochains tirages, mais nous ne pouvons rien changer aux exemplaires déjà chez vous.',
        'visibility_changed' => 'C’est noté : seuls les proches que vous avez choisis peuvent écouter.',
    ],

    'already_recorded' => [
        'title' => 'Vous avez déjà répondu à cette question',
        'title_with_date' => 'Vous avez déjà répondu à cette question le :date',
        'body' => 'Vous pouvez recommencer si vous préférez une autre version. Votre premier enregistrement est conservé.',
        'restart' => 'Recommencer',
        'close' => 'Fermer',
    ],

];
