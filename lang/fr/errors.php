<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Les pages d'erreur
|--------------------------------------------------------------------------
|
| Trois règles, tirées de la §16 des conventions et du ton du dossier :
|
|  1. **Dire ce qui se passe**, en langage simple. Pas de code d'erreur en
|     gros caractères, pas de « Oups », pas un mot d'anglais.
|  2. **Ne jamais accuser la personne.** Une adresse qui ne mène nulle part
|     est le plus souvent notre lien qui a changé, pas sa faute de frappe.
|  3. **Proposer une reprise.** Une page sans issue oblige à fermer l'onglet,
|     et quelqu'un de quatre-vingts ans ne revient pas.
|
*/

return [
    'back' => 'Revenir à l’accueil',

    '404' => [
        'title' => 'Cette page n’existe pas',
        'body' => 'L’adresse est peut-être incomplète, ou la page a changé de place. Rien n’est perdu : tout se retrouve depuis l’accueil.',
    ],

    '403' => [
        'title' => 'Cette page ne vous est pas ouverte',
        'body' => 'Il faut un lien personnel pour y accéder. Demandez-le à la personne qui vous a invité.',
    ],

    // 419 : la session a expiré. Le mot « session » ne dit rien à personne.
    '419' => [
        'title' => 'La page est restée ouverte trop longtemps',
        'body' => 'Par sécurité, nous avons refermé la page. Rouvrez-la et recommencez : rien de ce que vous aviez validé n’a été perdu.',
    ],

    '429' => [
        'title' => 'Un instant, s’il vous plaît',
        'body' => 'Trop de tentatives en peu de temps. Attendez une minute avant de réessayer.',
    ],

    '500' => [
        'title' => 'Quelque chose s’est mal passé chez nous',
        'body' => 'L’erreur vient de notre côté, pas du vôtre. Nous en sommes prévenus. Réessayez dans quelques minutes.',
    ],

    '503' => [
        'title' => 'Nous revenons dans un instant',
        'body' => 'Une mise à jour est en cours. Vos enregistrements et vos histoires ne sont pas concernés.',
    ],
];
