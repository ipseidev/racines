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
        'skip' => 'Vai al contenuto',
    ],

    'link_unavailable' => [
        'not_found' => [
            'title' => 'Questo link non funziona',
            'body' => 'Forse il link è incompleto. Controlla di averlo aperto per intero, dal messaggio che hai ricevuto.',
        ],
        'expired' => [
            'title' => 'Questo link è scaduto',
            'body' => 'Chiedi un nuovo link alla persona che ti ha invitato.',
        ],
        'revoked' => [
            'title' => 'Questo link non è più valido',
            'body' => 'La famiglia ha ritirato questo accesso. Chiedi un nuovo link alla persona che ti ha invitato.',
        ],
        'used' => [
            'title' => 'Questo link è già stato usato',
            'body' => 'Chiedi un nuovo link alla persona che ti ha invitato.',
        ],
        'type_mismatch' => [
            'title' => 'Questo link non porta qui',
            'body' => 'Corrisponde a un’altra pagina. Apri il link dal messaggio che hai ricevuto.',
        ],
        'help' => 'Hai bisogno di aiuto? Scrivici a :email.',
    ],

    'home' => [
        'eyebrow' => 'Ascolto',
        'intro' => 'Ascolta la sua voce, poi lascia due parole. Sono quelle parole a dare la voglia di raccontare la prossima storia.',
        'intro_empty' => 'Ti avviseremo appena arriva una storia.',
        'count' => '{1} Una storia condivisa|]1,*[ :count storie condivise',
        'reacted_by_you' => 'Hai risposto',
        'title' => 'Le storie di :first_name',
        'title_generic' => 'Le storie di chi ti è caro',
        'empty' => 'Per ora nessuna storia è stata condivisa. Ti avviseremo.',
        'new' => 'Nuova',
        'duration' => ':minutes min',
        // Dit pourquoi cette personne a ce lien, et ce qu'on attend d'elle.
        // C'est la seule protection contre sa circulation dans un groupe de
        // messagerie, et elle vaut mieux qu'une mention en petits caractères.
        'footer' => 'Ricevi questo link perché :inviter ti ha invitato. Inoltralo solo ai familiari.',
        'footer_generic' => 'Questo link è personale. Inoltralo solo ai familiari.',
    ],

    'story' => [
        'eyebrow' => 'Una storia di :first_name',
        'eyebrow_generic' => 'Una storia',
        'listen' => 'Da ascoltare',
        'photo_alt' => 'Foto aggiunta da :first_name',
        'someone' => 'un familiare',
        'photos' => 'Le foto',
        'add_photo' => 'Aggiungi una foto',
        // Dit à qui lit d'où vient le texte. La voix reste la référence : la
        // mention nomme la personne, pas le modèle, et le lecteur peut
        // toujours écouter l'enregistrement d'origine (bloc 08).
        'ai_label' => 'Testo reso scorrevole da un’IA, a partire dalla voce di :first_name',
        'untitled' => 'Una storia di :first_name',
        'tab_text' => 'Testo',
        'tab_verbatim' => 'Parola per parola',
        'previous' => 'Storia precedente',
        'next' => 'Storia successiva',
        'back' => 'Tutte le storie',
        'reacted' => 'Hanno risposto:',
        'no_audio' => 'La registrazione non è ancora disponibile all’ascolto.',
    ],

    'story_unavailable' => [
        'title' => 'Questa storia non è disponibile',
        // Aucune explication : le narrateur n'a pas à justifier ses retraits
        // auprès de sa famille, et dire « elle est masquée » reviendrait à
        // révéler qu'elle existe.
        'body' => 'Puoi tornare all’elenco delle storie condivise con te.',
    ],

    'reaction' => [
        'heart' => 'Mi è piaciuta',
        'thanks' => 'Grazie',
        'title' => 'Due parole per :first_name',
        'title_generic' => 'Due parole',
        'comment_label' => 'Lascia due parole',
        'comment_help' => 'Bastano poche parole. :first_name le riceverà.',
        'comment_counter' => ':count caratteri su :max',
        'send' => 'Invia',
        'sent' => 'Inviato. Grazie da parte di :first_name.',
        'sent_generic' => 'Inviato.',
        'eyebrow' => 'La tua risposta',
        'done' => 'Inviato',
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
        'code_title' => 'Con questo libro ti è stato dato un codice',
        'code_help' => 'La famiglia di :first_name ha scelto di proteggere l’ascolto. Il codice si trova sul risvolto del libro oppure ti è stato comunicato dalla persona che te lo ha regalato.',
        'code_label' => 'Il codice',
        'code_submit' => 'Ascolta',
        'wrong_code' => 'Questo codice non corrisponde. Controllalo sul risvolto del libro.',
        'too_many' => 'Troppi tentativi. Riprova tra un’ora.',
        'unavailable_title' => 'Questa storia non è più disponibile online',
        'unavailable_help' => 'Il testo stampato resta tuo. L’ascolto online è stato ritirato su richiesta di chi racconta o della sua famiglia.',
        'all_stories' => 'Vedi tutte le storie',
        'all_stories_help' => 'Per farlo serve un link personale: chiedilo alla persona che ti ha regalato questo libro.',
    ],
];
