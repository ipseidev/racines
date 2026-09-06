<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Les exports
|--------------------------------------------------------------------------
|
| Le ton de la non-captivité : ces fichiers sont à la famille, et le service
| n'est qu'un intermédiaire. Un lien expiré ne se dit donc pas « invalide » —
| il se dit « en voici un nouveau ».
|
*/

return [
    'expired' => [
        'title' => 'Ce lien a expiré',
        'body' => 'Les liens de téléchargement ne durent que quelques jours, par sécurité. Vos données, elles, n’ont pas bougé : demandez un nouveau lien depuis votre espace, gratuitement et autant de fois que vous le souhaitez.',
    ],

    'building' => [
        'title' => 'Votre dossier est en cours de préparation',
        'body' => 'Rassembler les enregistrements prend quelques minutes. Vous recevrez un courriel dès qu’il sera prêt : inutile de rester sur cette page.',
    ],
];
