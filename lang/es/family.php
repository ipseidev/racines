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
        'skip' => 'Ir al contenido',
    ],

    'link_unavailable' => [
        'not_found' => [
            'title' => 'Este enlace no funciona',
            'body' => 'Puede que el enlace esté incompleto. Comprueba que lo has abierto entero, desde el mensaje que has recibido.',
        ],
        'expired' => [
            'title' => 'Este enlace ha caducado',
            'body' => 'Pide un nuevo enlace a la persona que te ha invitado.',
        ],
        'revoked' => [
            'title' => 'Este enlace ya no es válido',
            'body' => 'La familia ha retirado este acceso. Pide un nuevo enlace a la persona que te ha invitado.',
        ],
        'used' => [
            'title' => 'Este enlace ya se ha usado',
            'body' => 'Pide un nuevo enlace a la persona que te ha invitado.',
        ],
        'type_mismatch' => [
            'title' => 'Este enlace no lleva aquí',
            'body' => 'Corresponde a otra página. Abre el enlace desde el mensaje que has recibido.',
        ],
        'help' => '¿Necesitas ayuda? Escríbenos a :email.',
    ],

    'home' => [
        'eyebrow' => 'Escucha',
        'intro' => 'Escucha su voz y luego dile unas palabras. Son esas palabras las que le dan ganas de contar la siguiente.',
        'intro_empty' => 'Te avisaremos en cuanto llegue una historia.',
        'count' => '{1} Una historia compartida|]1,*[ :count historias compartidas',
        'reacted_by_you' => 'Has reaccionado',
        'title' => 'Las historias de :first_name',
        'title_generic' => 'Las historias de tu ser querido',
        'empty' => 'Todavía no hay ninguna historia compartida. Te avisaremos.',
        'new' => 'Nueva',
        'duration' => ':minutes min',
        // Dit pourquoi cette personne a ce lien, et ce qu'on attend d'elle.
        // C'est la seule protection contre sa circulation dans un groupe de
        // messagerie, et elle vaut mieux qu'une mention en petits caractères.
        'footer' => 'Recibes este enlace porque :inviter te ha invitado. Compártelo únicamente con familiares.',
        'footer_generic' => 'Este enlace es personal. Compártelo únicamente con familiares.',
    ],

    'story' => [
        'eyebrow' => 'Una historia de :first_name',
        'eyebrow_generic' => 'Una historia',
        'listen' => 'Para escuchar',
        'photo_alt' => 'Foto añadida por :first_name',
        'someone' => 'un familiar',
        'photos' => 'Las fotos',
        'add_photo' => 'Añadir una foto',
        // Dit à qui lit d'où vient le texte. La voix reste la référence : la
        // mention nomme la personne, pas le modèle, et le lecteur peut
        // toujours écouter l'enregistrement d'origine (bloc 08).
        'ai_label' => 'Texto pasado a limpio por una IA, a partir de la voz de :first_name',
        'untitled' => 'Una historia de :first_name',
        'tab_text' => 'Texto',
        'tab_verbatim' => 'Palabra por palabra',
        'previous' => 'Historia anterior',
        'next' => 'Historia siguiente',
        'back' => 'Todas las historias',
        'reacted' => 'Han reaccionado:',
        'no_audio' => 'La grabación todavía no está disponible para escuchar.',
    ],

    'story_unavailable' => [
        'title' => 'Esta historia no está disponible',
        // Aucune explication : le narrateur n'a pas à justifier ses retraits
        // auprès de sa famille, et dire « elle est masquée » reviendrait à
        // révéler qu'elle existe.
        'body' => 'Puedes volver a la lista de historias compartidas contigo.',
    ],

    'reaction' => [
        'heart' => 'Me ha gustado',
        'thanks' => 'Gracias',
        'title' => 'Decirle unas palabras a :first_name',
        'title_generic' => 'Decir unas palabras',
        'comment_label' => 'Dejar unas palabras',
        'comment_help' => 'Con unas pocas palabras basta. :first_name las recibirá.',
        'comment_counter' => ':count caracteres de :max',
        'send' => 'Enviar',
        'sent' => 'Enviado. Gracias de parte de :first_name.',
        'sent_generic' => 'Enviado.',
        'eyebrow' => 'Tu respuesta',
        'done' => 'Enviado',
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
        'code_title' => 'Con este libro te han dado un código',
        'code_help' => 'La familia de :first_name ha decidido proteger la escucha. El código está en la solapa del libro o te lo ha dado la persona que te lo ha regalado.',
        'code_label' => 'El código',
        'code_submit' => 'Escuchar',
        'wrong_code' => 'Este código no coincide. Compruébalo en la solapa del libro.',
        'too_many' => 'Demasiados intentos. Vuelve a intentarlo dentro de una hora.',
        'unavailable_title' => 'Esta historia ya no está disponible en línea',
        'unavailable_help' => 'El texto impreso sigue siendo tuyo. La escucha en línea se ha retirado a petición de quien narra o de su familia.',
        'all_stories' => 'Ver todas las historias',
        'all_stories_help' => 'Para eso necesitas un enlace personal: pídeselo a la persona que te ha regalado este libro.',
    ],
];
