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

];
