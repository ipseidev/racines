<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cette réponse n'est pas une page : elle ne doit pas devenir celle où l'on
 * revient.
 *
 * `StartSession` note l'adresse de **toute** requête `GET` non-ajax comme
 * « page précédente », sans regarder ce qu'elle a rendu. Un manifeste, une
 * fiche contact, une archive : le navigateur les demande de lui-même, à un
 * moment qu'il choisit, et la dernière gagne. Le `back()` d'un formulaire y
 * retourne alors — et Inertia, qui suit la redirection, reçoit du JSON.
 *
 * C'est ce qui s'est passé sur l'écran de partage : Safari avait récupéré
 * `/site.webmanifest` après le chargement de la page, le narrateur a cliqué
 * « Partager avec mes proches », et il a lu « All Inertia requests must
 * receive a valid Inertia response, however a plain JSON response was
 * received » — suivi du manifeste (T-231).
 *
 * On ne peut pas le corriger dans la route : `storeCurrentUrl()` s'exécute
 * **après** toute la pile d'intergiciels, donc après ce qu'un intergiciel de
 * route pourrait remettre. On le corrige donc à la fin de la requête, quand
 * l'écriture a eu lieu — `terminate()` — en reposant l'adresse d'avant et en
 * réenregistrant la session.
 *
 * Le geste est petit et la règle est générale : toute route qui rend autre
 * chose qu'une page se déclare ainsi.
 */
final class NotAPage
{
    /**
     * L'adresse est rangée **dans la requête** et non dans une propriété.
     *
     * Laravel résout l'intergiciel une fois pour `handle()` et une **autre**
     * fois pour `terminate()` : un état d'instance est perdu entre les deux,
     * et la correction ne s'appliquait jamais. La requête, elle, est la même
     * objet dans les deux appels.
     */
    private const AVANT = 'not_a_page.previous';

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->hasSession()) {
            // Lu **avant** la réponse : c'est la dernière vraie page, celle
            // qu'on doit retrouver intacte.
            $request->attributes->set(self::AVANT, $request->session()->previousUrl());
        }

        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        $previous = $request->attributes->get(self::AVANT);

        if (! is_string($previous) || ! $request->hasSession()) {
            return;
        }

        $session = $request->session();

        if ($session->previousUrl() === $previous) {
            return;
        }

        $session->setPreviousUrl($previous);

        // `StartSession` a déjà sauvegardé : sans ce second appel, la
        // correction ne survivrait pas à la requête.
        $session->save();
    }
}
