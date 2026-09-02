<?php

declare(strict_types=1);

use App\Enums\TokenType;
use App\Exceptions\Domain\StoryUnavailable;
use App\Exceptions\Domain\TokenUnavailable;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\NoStore;
use App\Http\Middleware\RequireSensitiveGrant;
use App\Http\Middleware\ResolveAccessToken;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            // Un jeton fait 43 caractères de base64url, et rien d'autre n'est
            // une route : un lien tronqué ou bricolé reçoit un 404 sans
            // qu'aucune requête ne touche la base (bloc 03 §5).
            Route::pattern('token', '[A-Za-z0-9_-]{43}');

            // Espaces narrateur et famille : servis sur le domaine court des
            // liens, sans compte, par jeton porteur (doc 04 §9 et §12).
            Route::middleware('web')
                ->domain(config('brand.links_domain'))
                ->group(base_path('routes/narrator.php'));

            Route::middleware('web')
                ->domain(config('brand.links_domain'))
                ->group(base_path('routes/family.php'));

            // Webhooks : ni session ni CSRF, signature vérifiée par route.
            Route::group([], base_path('routes/webhooks.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            SecurityHeaders::class,
        ]);

        $middleware->alias([
            'resolve.token' => ResolveAccessToken::class,
            'no-store' => NoStore::class,
            'sensitive' => RequireSensitiveGrant::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Un lien mort ne produit jamais une erreur technique : il produit une
        // page en langage simple, avec une action de reprise (convention §16).
        $exceptions->render(function (TokenUnavailable $exception, Request $request) {
            $space = $exception->tokenType()?->space() ?? 'family';

            $status = in_array($exception->reason(), ['expired', 'revoked', 'used'], true)
                ? 410   // Gone : le lien a existé, il ne vaut plus.
                : 404;  // Not Found : il n'a jamais existé, ou pas ici.

            return Inertia::render($space.'/LinkUnavailable', [
                'reason' => $exception->reason(),
                'canRequestNewLink' => $exception->canRequestNewLink(),
                'tokenType' => $exception->tokenType()?->value,
            ])->toResponse($request)->setStatusCode($status);
        });

        // Une histoire hors de portée : même exigence. Le message ne dit pas
        // pourquoi — un proche qui apprendrait qu'une histoire existe mais
        // lui est refusée en saurait déjà trop.
        $exceptions->render(function (StoryUnavailable $exception, Request $request) {
            $token = $request->route('token');

            return Inertia::render('family/StoryUnavailable', [
                'backUrl' => is_string($token) ? '/l/'.$token : null,
            ])->toResponse($request)->setStatusCode(404);
        });
    })->create();
