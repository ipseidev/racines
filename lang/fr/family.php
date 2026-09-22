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

    'nav' => [
        'skip' => 'Aller au contenu',
    ],

    'link_unavailable' => [
        'not_found' => [
            'title' => 'Ce lien ne fonctionne pas',
            'body' => 'Le lien est peut-être incomplet. Vérifiez que vous l’avez ouvert en entier, depuis le message que vous avez reçu.',
        ],
        'expired' => [
            'title' => 'Ce lien a expiré',
            'body' => 'Demandez un nouveau lien à la personne qui vous l’a envoyé.',
        ],
        'revoked' => [
            'title' => 'Ce lien n’est plus valable',
            'body' => 'La famille a retiré cet accès. Demandez un nouveau lien à la personne qui vous l’a envoyé.',
        ],
        'used' => [
            'title' => 'Ce lien a déjà servi',
            'body' => 'Demandez un nouveau lien à la personne qui vous l’a envoyé.',
        ],
        'type_mismatch' => [
            'title' => 'Ce lien ne mène pas ici',
            'body' => 'Il correspond à une autre page. Ouvrez le lien depuis le message que vous avez reçu.',
        ],
        'help' => 'Besoin d’aide ? Écrivez-nous à :email.',
    ],

    'home' => [
        'eyebrow' => 'Écoute',
        'intro' => 'Écoutez sa voix, puis dites-lui un mot. C’est ce mot qui lui donne envie de raconter la suivante.',
        'intro_empty' => 'Vous serez prévenu·e dès qu’une histoire arrive.',
        'count' => '{1} Une histoire partagée|]1,*[ :count histoires partagées',
        'reacted_by_you' => 'Vous avez réagi',
        'title' => 'Les histoires de :first_name',
        'title_generic' => 'Les histoires de votre proche',
        'empty' => 'Aucune histoire n’est partagée pour l’instant. Vous serez prévenu·e.',
        'new' => 'Nouvelle',
        'duration' => ':minutes min',
        // Dit pourquoi cette personne a ce lien, et ce qu'on attend d'elle.
        // C'est la seule protection contre sa circulation dans un groupe de
        // messagerie, et elle vaut mieux qu'une mention en petits caractères.
        'footer' => 'Vous recevez ce lien de la part de :inviter. Ne le transmettez qu’à des proches.',
        'footer_generic' => 'Ce lien vous est personnel. Ne le transmettez qu’à des proches.',
    ],

    'story' => [
        'eyebrow' => 'Une histoire de :first_name',
        'eyebrow_generic' => 'Une histoire',
        'listen' => 'À écouter',
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
        'eyebrow' => 'Votre réponse',
        'done' => 'Envoyé',
    ],

    // Le lecteur audio vit dans `common.player` depuis T-138 : il sert aussi
    // à la narratrice qui se réécoute.

    /*
     * Les QR imprimés dans le livre (bloc 13).
     *
     * Le ton est celui d'un livre qu'on tient en main, pas celui d'un
     * formulaire : la personne qui scanne n'a pas de compte, n'a rien demandé
     * et ne sait pas forcément ce qu'est un code famille.
     */
    'qr' => [
        'code_title' => 'Un code vous a été donné avec ce livre',
        'code_help' => 'La famille de :first_name a choisi de protéger l’écoute. Le code figure sur le rabat du livre ou vous a été communiqué par la personne qui vous l’a offert.',
        'code_label' => 'Le code',
        'code_submit' => 'Écouter',
        'wrong_code' => 'Ce code ne correspond pas. Vérifiez-le sur le rabat du livre.',
        'too_many' => 'Trop d’essais. Réessayez dans une heure.',
        'unavailable_title' => 'Cette histoire n’est plus disponible en ligne',
        'unavailable_help' => 'Le texte imprimé reste le vôtre. L’écoute en ligne a été retirée à la demande du narrateur ou de sa famille.',
        'all_stories' => 'Voir toutes les histoires',
        'all_stories_help' => 'Il vous faut pour cela un lien personnel : demandez-le à la personne qui vous a offert ce livre.',
    ],

    /*
     * Une question posée par un proche (R-1, dossier v3.1).
     *
     * Le mot « question » et pas « suggestion » : on demande vraiment quelque
     * chose à quelqu'un, et l'édulcorer ferait croire que ça n'arrivera
     * peut-être pas. Ça arrive, et la personne est libre de ne pas répondre —
     * c'est dit.
     */
    'ask' => [
        'title' => 'Posez-lui votre question',
        'intro' => 'Ce que vous aimeriez savoir, et que personne d’autre ne pensera à demander. Elle partira à son tour, après celles qui attendent déjà.',
        'label' => 'Votre question',
        'placeholder' => 'Comment vous êtes-vous rencontrés, mamie ?',
        'photos' => 'Joindre des photos',
        'photos_help' => 'Elles seront montrées en même temps que votre question : une photo appelle souvent un récit que les mots ne trouvent pas. Quatre au plus.',
        'submit' => 'Envoyer ma question',
        'sending' => 'Envoi…',
        'added' => 'Votre question est dans la file. :name la recevra à son tour.',
        'added_without_photo' => 'Votre question est posée, mais une photo n’a pas pu être jointe.',
        'free' => 'Rien ne l’oblige à y répondre, et personne ne le lui demandera deux fois.',
        'mine' => 'Vos questions',
        'waiting' => 'En attente',
        'sent' => 'Posée le :date',
    ],
];
