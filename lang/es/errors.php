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

    /*
     * Une écriture qui échoue, sans quitter la page.
     *
     * Le narrateur est au milieu de quelque chose : on lui dit ce qui se
     * passe et quoi faire, on ne l'envoie pas sur une autre page. Aucun de ces
     * messages ne l'accuse, et aucun ne porte de terme technique (T-229).
     */
    'inertia' => [
        'expired' => 'Esta página ha estado abierta un rato y su código de seguridad ha caducado. Recargue la página: su respuesta se conserva.',
        'too_many' => 'Ha intentado varias veces seguidas. Espere un minuto e inténtelo de nuevo.',
        'server' => 'Algo ha fallado por nuestra parte. Inténtelo de nuevo en un momento; si persiste, escríbanos.',
        'refused' => 'No se ha podido realizar esta acción. Recargue la página e inténtelo de nuevo.',
    ],
    'back' => 'Volver al inicio',

    '404' => [
        'title' => 'Esta página no existe',
        'body' => 'Puede que la dirección esté incompleta o que la página haya cambiado de sitio. No se ha perdido nada: lo encontrará todo desde la página de inicio.',
    ],

    '403' => [
        'title' => 'Esta página no está abierta para usted',
        'body' => 'Hace falta un enlace personal para acceder a ella. Pídaselo a la persona que le ha invitado.',
    ],

    // 419 : la session a expiré. Le mot « session » ne dit rien à personne.
    '419' => [
        'title' => 'La página ha estado abierta demasiado tiempo',
        'body' => 'Por seguridad, hemos cerrado la página. Vuelva a abrirla y empiece de nuevo: nada de lo que había validado se ha perdido.',
    ],

    '429' => [
        'title' => 'Un momento, por favor',
        'body' => 'Demasiados intentos en poco tiempo. Espere un minuto antes de intentarlo de nuevo.',
    ],

    '500' => [
        'title' => 'Algo ha salido mal por nuestra parte',
        'body' => 'El error viene de nuestro lado, no del suyo. Ya estamos al tanto. Inténtelo de nuevo en unos minutos.',
    ],

    '503' => [
        'title' => 'Volvemos en un momento',
        'body' => 'Estamos haciendo una actualización. Sus grabaciones y sus historias no se ven afectadas.',
    ],
];
