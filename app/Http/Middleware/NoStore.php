<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pages à jeton : rien en cache, rien indexé, rien de référent.
 *
 * Un lien d'enregistrement est ouvert sur un téléphone parfois partagé, et
 * pourrait finir dans l'index d'un moteur si un narrateur le recopiait
 * quelque part. Ces trois en-têtes ferment les trois fuites : le cache du
 * navigateur ou d'un proxy, l'indexation, et la transmission de l'URL — donc
 * du jeton — au site suivant par l'en-tête `Referer`.
 */
final class NoStore
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, private');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        $response->headers->set('Referrer-Policy', 'no-referrer');

        return $response;
    }
}
