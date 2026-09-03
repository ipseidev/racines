<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Espace des proches
|--------------------------------------------------------------------------
|
| Lecture seule. Un proche ne demande jamais un nouveau lien au produit : il
| le redemande à la personne qui l'a invité, qui seule décide de qui écoute.
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
            'body' => 'Demandez un nouveau lien à la personne qui vous a invité·e.',
        ],
        'revoked' => [
            'title' => 'Ce lien n’est plus valable',
            'body' => 'La famille a retiré cet accès. Demandez un nouveau lien à la personne qui vous a invité·e.',
        ],
        'used' => [
            'title' => 'Ce lien a déjà servi',
            'body' => 'Demandez un nouveau lien à la personne qui vous a invité·e.',
        ],
        'type_mismatch' => [
            'title' => 'Ce lien ne mène pas ici',
            'body' => 'Il correspond à une autre page. Ouvrez le lien depuis le message que vous avez reçu.',
        ],
        'help' => 'Besoin d’aide ? Écrivez-nous à :email.',
    ],

    'home' => [
        'title' => 'Les histoires de :first_name',
        'title_generic' => 'Les histoires de votre proche',
        'empty' => 'Aucune histoire n’est partagée pour l’instant. Vous serez prévenu·e.',
        'new' => 'Nouvelle',
        'duration' => ':minutes min',
        // Dit pourquoi cette personne a ce lien, et ce qu'on attend d'elle.
        // C'est la seule protection contre sa circulation dans un groupe de
        // messagerie, et elle vaut mieux qu'une mention en petits caractères.
        'footer' => 'Vous recevez ce lien parce que :inviter vous a invité·e. Ne le transmettez qu’à des proches.',
        'footer_generic' => 'Ce lien vous est personnel. Ne le transmettez qu’à des proches.',
    ],

    'story' => [
        'photo_alt' => 'Photo jointe par :first_name',
        'someone' => 'un proche',
        'photos' => 'Les photos',
        'add_photo' => 'Ajouter une photo',
        // Dit à qui lit d'où vient le texte. La voix reste la référence : la
        // mention nomme la personne, pas le modèle, et le lecteur peut
        // toujours écouter l'enregistrement d'origine (bloc 08).
        'ai_label' => 'Texte mis au propre par une IA, à partir de la voix de :first_name',
        'untitled' => 'Une histoire de :first_name',
        'tab_text' => 'Texte',
        'tab_verbatim' => 'Mot à mot',
        'previous' => 'Histoire précédente',
        'next' => 'Histoire suivante',
        'back' => 'Toutes les histoires',
        'reacted' => 'Ont réagi :',
        'no_audio' => 'L’enregistrement n’est pas encore disponible à l’écoute.',
    ],

    'story_unavailable' => [
        'title' => 'Cette histoire n’est pas disponible',
        // Aucune explication : le narrateur n'a pas à justifier ses retraits
        // auprès de sa famille, et dire « elle est masquée » reviendrait à
        // révéler qu'elle existe.
        'body' => 'Vous pouvez revenir à la liste des histoires partagées avec vous.',
    ],

    'reaction' => [
        'heart' => 'J’ai aimé',
        'thanks' => 'Merci',
        'title' => 'Dire un mot à :first_name',
        'title_generic' => 'Dire un mot',
        'comment_label' => 'Laisser un mot',
        'comment_help' => 'Quelques mots suffisent. :first_name les recevra.',
        'comment_counter' => ':count caractères sur :max',
        'send' => 'Envoyer',
        'sent' => 'C’est envoyé. Merci pour :first_name.',
        'sent_generic' => 'C’est envoyé.',
    ],

    'player' => [
        'play' => 'Écouter',
        'pause' => 'Mettre en pause',
        'back15' => 'Reculer de 15 secondes',
        'forward15' => 'Avancer de 15 secondes',
        'slower' => 'Ralentir un peu',
        'normal' => 'Vitesse normale',
        'remaining' => 'Il reste :time',
        'progress' => 'Progression de l’écoute',
    ],

];
