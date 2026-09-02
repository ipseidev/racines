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

];
