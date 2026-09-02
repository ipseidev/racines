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

    'probe' => [
        'title' => 'Lien reconnu',
        'body' => 'Ce lien est valable. La page d’enregistrement arrive bientôt.',
        'token_type' => 'Type de lien',
        'subject' => 'Concerne',
        'expires_at' => 'Valable jusqu’au',
    ],

];
