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
        'expired' => 'Questa pagina è rimasta aperta per un po’ e il suo codice di sicurezza è scaduto. Basta ricaricarla: la risposta è conservata.',
        'too_many' => 'Ha provato più volte di seguito. Aspetti un minuto, poi riprovi.',
        'server' => 'Qualcosa non ha funzionato da parte nostra. Riprovi tra un istante; se il problema continua, ci scriva.',
        'refused' => 'Non è stato possibile completare questa azione. Ricarichi la pagina e riprovi.',
    ],
    'back' => 'Torna alla pagina iniziale',

    '404' => [
        'title' => 'Questa pagina non esiste',
        'body' => 'Forse l’indirizzo è incompleto, oppure la pagina è stata spostata. Nulla è perduto: si ritrova tutto dalla pagina iniziale.',
    ],

    '403' => [
        'title' => 'Questa pagina è riservata',
        'body' => 'Per accedervi serve un link personale. Lo chieda a chi le ha mandato l’invito.',
    ],

    // 419 : la session a expiré. Le mot « session » ne dit rien à personne.
    '419' => [
        'title' => 'La pagina è rimasta aperta troppo a lungo',
        'body' => 'Per sicurezza abbiamo chiuso la pagina. La riapra e ricominci: nulla di ciò che aveva confermato è andato perduto.',
    ],

    '429' => [
        'title' => 'Un momento, per favore',
        'body' => 'Troppi tentativi in poco tempo. Aspetti un minuto prima di riprovare.',
    ],

    '500' => [
        'title' => 'Qualcosa è andato storto da parte nostra',
        'body' => 'L’errore è nostro, non suo. Siamo già stati avvisati. Riprovi tra qualche minuto.',
    ],

    '503' => [
        'title' => 'Torniamo tra pochi istanti',
        'body' => 'È in corso un aggiornamento. Le sue registrazioni e le sue storie non sono coinvolte.',
    ],
];
