<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * En-têtes de sécurité HTTP (convention §9, doc 04 §12).
 *
 * Deux politiques de contenu, et c'est délibéré :
 *
 *  - Les pages du produit — publiques, narrateur, proches, espace client —
 *    reçoivent une politique stricte : scripts et feuilles de style par
 *    `nonce` uniquement, aucun `unsafe-eval`, aucune origine tierce. Le
 *    `nonce` vient de `Vite::useCspNonce()`, posé avant le rendu.
 *  - Le back-office Filament reçoit une politique assouplie, parce qu'Alpine
 *    évalue ses expressions par `new Function()` : sans `unsafe-eval`, le
 *    panneau ne fonctionne pas. C'est un panneau réservé au personnel, à
 *    revoir au bloc 16 si Filament expose un jour un mode compatible.
 *
 * Le micro n'est autorisé que sur les pages d'enregistrement. Partout
 * ailleurs, `microphone=()` : une page compromise ne peut pas écouter.
 */
final class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $nonce = Vite::useCspNonce();

        $response = $next($request);

        // Les réponses en flux (téléchargements, exports) n'ont pas de corps à
        // protéger et certaines n'acceptent pas d'en-têtes tardifs.
        $response->headers->set('Content-Security-Policy', $this->policy($request, $nonce));
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Permissions-Policy', $this->permissionsPolicy($request));

        if (! $response->headers->has('Referrer-Policy')) {
            $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        }

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }

    private function policy(Request $request, string $nonce): string
    {
        // Le serveur de développement sert aussi des images et des polices :
        // son origine HTTP rejoint donc les hôtes de médias. Son websocket,
        // lui, n'a de sens que dans `connect-src` — un `ws://` dans `img-src`
        // serait du bruit, et le bruit est ce qui cache les erreurs dans un
        // en-tête de sécurité.
        [$devOrigin, $devSocket] = self::viteDevOrigins();

        $media = trim(implode(' ', array_filter([...self::mediaHosts(), $devOrigin])));
        $connect = trim(implode(' ', array_filter([...self::mediaHosts(), $devOrigin, $devSocket])));

        if ($this->isBackOffice($request)) {
            return implode('; ', [
                "default-src 'self'",
                "script-src 'self' 'unsafe-eval' 'unsafe-inline'",
                "style-src 'self' 'unsafe-inline'",
                "font-src 'self' data:",
                trim("img-src 'self' data: blob: {$media}"),
                trim("media-src 'self' blob: {$media}"),
                trim("connect-src 'self' {$connect}"),
                "object-src 'none'",
                "base-uri 'self'",
                "form-action 'self'",
                "frame-ancestors 'none'",
            ]);
        }

        return implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'nonce-{$nonce}'",
            "style-src 'self' 'nonce-{$nonce}'",
            // Les styles posés en attribut par React ne peuvent pas porter de
            // nonce. Les autoriser en attribut seulement laisse `style-src`
            // fermé aux feuilles et aux balises injectées.
            "style-src-attr 'unsafe-inline'",
            // Les polices sont auto-hébergées (T-40) : aucune origine tierce.
            "font-src 'self' data:",
            trim("img-src 'self' data: blob: {$media}"),
            trim("media-src 'self' blob: {$media}"),
            trim("connect-src 'self' {$connect}"),
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
        ]);
    }

    /**
     * Le micro reste autorisé sur les pages d'enregistrement du narrateur, et
     * nulle part ailleurs (convention §9).
     */
    private function permissionsPolicy(Request $request): string
    {
        $microphone = $request->is('r/*') ? 'microphone=(self)' : 'microphone=()';

        return implode(', ', [$microphone, 'camera=()', 'geolocation=()']);
    }

    /**
     * Reconnu au nom de la route plutôt qu'au chemin : le panneau peut être
     * déplacé dans `AdminPanelProvider` sans que cette politique décroche.
     */
    private function isBackOffice(Request $request): bool
    {
        return str_starts_with((string) $request->route()?->getName(), 'filament.');
    }

    /**
     * Le serveur de développement de Vite, quand il tourne, et jamais en
     * production.
     *
     * Sans lui, `connect-src` refusait `ws://localhost:5176` et le websocket
     * de rechargement à chaud ne se connectait pas. Vite retombait alors sur
     * une invalidation de module, qui réimportait `app.tsx` avec un `?t=` :
     * **deux instances du module, deux appels à `createRoot` sur le même
     * conteneur**. Une racine React mettait l'URL à jour, l'autre gardait
     * l'écran, et plus aucune navigation côté client ne fonctionnait en local
     * (T-129). Invisible en intégration continue, qui construit les assets et
     * ne lance donc jamais le serveur de développement.
     *
     * Deux gardes plutôt qu'une : le fichier `hot` doit exister **et**
     * l'application ne pas être en production. Un `hot` oublié dans une image
     * livrée n'ouvre ainsi aucune origine.
     *
     * @return array{0: string|null, 1: string|null} l'origine HTTP, puis celle du websocket
     */
    private static function viteDevOrigins(): array
    {
        if (app()->isProduction()) {
            return [null, null];
        }

        $hot = public_path('hot');

        if (! is_file($hot)) {
            return [null, null];
        }

        $parts = parse_url(trim((string) file_get_contents($hot)));

        if (! is_array($parts) || ! isset($parts['host'])) {
            return [null, null];
        }

        $scheme = is_string($parts['scheme'] ?? null) ? $parts['scheme'] : 'http';
        $authority = $parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '');
        $socket = $scheme === 'https' ? 'wss' : 'ws';

        return ["{$scheme}://{$authority}", "{$socket}://{$authority}"];
    }

    /**
     * Origines autorisées à servir des médias, lues dans la configuration :
     * R2 en production, MinIO en local (bloc 04).
     *
     * @return list<string>
     */
    private static function mediaHosts(): array
    {
        $hosts = config('product.security.media_hosts');

        if (! is_array($hosts)) {
            return [];
        }

        $origins = [];

        foreach ($hosts as $host) {
            if (! is_string($host) || $host === '') {
                continue;
            }

            $parts = parse_url($host);

            if (is_array($parts) && isset($parts['scheme'], $parts['host'])) {
                $port = isset($parts['port']) ? ':'.$parts['port'] : '';
                $origins[] = $parts['scheme'].'://'.$parts['host'].$port;

                continue;
            }

            $origins[] = $host;
        }

        return array_values(array_unique($origins));
    }
}
