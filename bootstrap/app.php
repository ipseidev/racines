<?php

declare(strict_types=1);

use App\Enums\TokenType;
use App\Exceptions\Domain\NoInitiatorProject;
use App\Exceptions\Domain\StoryUnavailable;
use App\Exceptions\Domain\TokenUnavailable;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\NoStore;
use App\Http\Middleware\NotAPage;
use App\Http\Middleware\RequireSensitiveGrant;
use App\Http\Middleware\ResolveAccessToken;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use App\Support\Links;
use App\Support\Locales;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;
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
            // liens, sans compte, par jeton porteur (doc 04 §9 et §12). Hors
            // production, aucun domaine n'est contraint — voir
            // `Links::routeDomain()`, qui dit pourquoi.
            $linksDomain = Links::routeDomain();

            foreach (['narrator', 'family'] as $file) {
                $group = Route::middleware('web');

                if ($linksDomain !== null) {
                    $group = $group->domain($linksDomain);
                }

                $group->group(base_path("routes/{$file}.php"));
            }

            // Webhooks : ni session ni CSRF, signature vérifiée par route.
            Route::group([], base_path('routes/webhooks.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        /*
         * Les cookies que le serveur lit sans les avoir écrits : posés par du
         * JavaScript, ils ne sont pas chiffrés, et un cookie que le chiffreur
         * ne sait pas déchiffrer lui arrive **nul** — silencieusement. Le
         * consentement (T-227) décide de ce qu'on transmet à Meta au paiement,
         * `_fbp` et `_fbc` sont les identifiants de clic du pixel (T-226).
         */
        $middleware->encryptCookies(except: ['sidebar_state', 'consentement', '_fbp', '_fbc']);

        $middleware->web(append: [
            // En tête : la langue décide de ce que rendent tous les autres —
            // les traductions partagées par Inertia, les libellés d'erreur,
            // les dates. Après le routage, parce qu'une page publique tient
            // sa langue de son adresse (`/it/come-funziona`).
            SetLocale::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            SecurityHeaders::class,
        ]);

        $middleware->alias([
            // Une réponse qui n'est pas une page : elle ne doit pas devenir
            // celle où un `back()` revient (T-231).
            'not-a-page' => NotAPage::class,
            'resolve.token' => ResolveAccessToken::class,
            'no-store' => NoStore::class,
            'sensitive' => RequireSensitiveGrant::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        /*
         * Une page d'erreur parle la langue de l'adresse demandée.
         *
         * Une adresse qui n'existe pas n'a pas de route, donc pas de groupe
         * « web » et pas de `SetLocale` : sans cette ligne, `/it/inexistante`
         * répondait en français. Le préfixe suffit — il n'y a ni session ni
         * témoin déchiffré à ce stade. La fermeture ne rend rien : elle pose
         * la langue et laisse le rendu ordinaire suivre son cours.
         */
        $exceptions->render(function (Throwable $exception, Request $request): null {
            $locale = SetLocale::fromPath($request);

            if ($locale !== null) {
                Locales::set($locale);
            }

            return null;
        });

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

        /*
         * Pas encore de projet : une page, pas une erreur.
         *
         * Le tableau de bord savait déjà le dire ; les cinq autres onglets de
         * l'espace répondaient par la page brute de Laravel (T-199). La
         * réponse est **200** : la route existe, la personne y a droit, il n'y
         * a simplement rien encore — un 404 dirait le contraire des trois.
         *
         * Sur une écriture, en revanche, il n'y a rien à montrer : un 404 sec,
         * qui ne sera lu par personne puisque le bouton n'existait pas.
         */
        $exceptions->render(function (NoInitiatorProject $exception, Request $request) {
            if (! $request->isMethod('GET')) {
                return response('', 404);
            }

            return Inertia::render('initiator/NoProject')->toResponse($request);
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

        /*
         * Une erreur sur une **écriture Inertia** : on reste sur la page, avec
         * un message.
         *
         * Les pages d'erreur en Blade existent et sont volontairement sans
         * JavaScript (T-199) — elles doivent tenir quand le bundle ne se
         * charge pas. Mais Inertia ne sait pas consommer du HTML : sur un
         * `POST`, sa réponse à ces pages est « All Inertia Requests must
         * receive a valid Inertia response », un message de développeur
         * affiché à un narrateur de quatre-vingts ans qui vient de cliquer
         * « Partager avec mes proches » (T-229).
         *
         * Le cas le plus fréquent est **419**, la session expirée : la page
         * d'enregistrement reste ouverte longtemps — on y parle, on réécoute,
         * on recommence — et le jeton de formulaire vieillit pendant ce
         * temps. Renvoyer la personne sur une page d'erreur lui ferait perdre
         * ce qu'elle est en train de faire ; `back()` la laisse où elle est,
         * avec une phrase qui dit quoi faire.
         *
         * Seules les écritures sont traitées ici. Une navigation `GET` qui
         * échoue est un lien cassé — un défaut à corriger, pas une situation
         * à habiller — et elle garde la page Blade, qui s'affiche même sans
         * JavaScript. C'est précisément le moment où on la veut.
         */
        $exceptions->respond(function (Response $response, Throwable $exception, Request $request) {
            $inertia = $request->hasHeader('X-Inertia');
            $status = $response->getStatusCode();

            if (! $inertia || $request->isMethod('GET') || $status < 400) {
                return $response;
            }

            return back()->with('error', match (true) {
                $status === 419 => __('errors.inertia.expired'),
                $status === 429 => __('errors.inertia.too_many'),
                $status >= 500 => __('errors.inertia.server'),
                default => __('errors.inertia.refused'),
            });
        });
    })->create();
