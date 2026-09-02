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

    'story' => [
        // Dit à qui lit d'où vient le texte. La voix reste la référence : la
        // mention nomme la personne, pas le modèle, et le lecteur peut
        // toujours écouter l'enregistrement d'origine (bloc 08).
        'ai_label' => 'Texte mis au propre par une IA, à partir de la voix de :first_name',
    ],

];
