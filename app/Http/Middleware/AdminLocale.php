<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\Locale;
use App\Support\Locales;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * L'administration parle français, quelle que soit la langue du témoin.
 *
 * C'est l'outil interne d'une équipe française (`lang/fr/admin.php` n'a pas
 * de traduction, et n'en aura pas tant que l'équipe ne change pas) : une
 * opératrice qui vient de relire la page d'accueil italienne ne doit pas
 * retrouver Filament en italien et ses libellés métier en français.
 */
final class AdminLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        Locales::set(Locale::French);

        return $next($request);
    }
}
