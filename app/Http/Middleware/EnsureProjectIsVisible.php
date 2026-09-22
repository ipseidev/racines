<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Project;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Le projet de l'adresse appartient bien à qui le demande — ou 404.
 *
 * `can:view,project` ferait le travail, mais répondrait **403**, et un 403
 * confirme que le projet existe. La maison répond 404 dans ce cas depuis le
 * bloc 03 : le motif de route rejette un lien malformé avant toute requête,
 * et une histoire qui n'est pas la vôtre n'existe pas pour vous. Un espace
 * de famille ne doit pas se laisser énumérer plus qu'un lien à jeton.
 *
 * La règle elle-même reste dans `ProjectPolicy::view()` — donc `isMember()`,
 * donc le propriétaire et l'éditeur désigné du livre. On ne recopie pas la
 * garde ici : une garde en double finit par diverger, et c'est la copie
 * oubliée qui laisse passer.
 */
final class EnsureProjectIsVisible
{
    public function handle(Request $request, Closure $next): Response
    {
        $project = $request->route()?->parameter('project');
        $user = $request->user();

        abort_unless(
            $project instanceof Project && $user !== null && $user->can('view', $project),
            404,
        );

        return $next($request);
    }
}
