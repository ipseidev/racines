<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\Locale;
use App\Support\Locales;
use App\Support\LocalizedRoutes;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * Décide la langue de la requête, une fois pour toutes.
 *
 * Dans l'ordre :
 *
 *  1. **L'adresse**, sur une page publique déclinée : `/it/come-funziona`
 *     est en italien, quoi qu'en disent le témoin ou le navigateur — une
 *     adresse indexée doit toujours servir la même langue. Le français vit
 *     à la racine, sans préfixe.
 *  2. **Le témoin** `locale`, posé quand on a visité une page préfixée ou
 *     choisi une langue dans le sélecteur : c'est le dernier choix explicite.
 *  3. **Le compte**, pour une personne connectée sur un appareil neuf.
 *  4. **Le navigateur** (`Accept-Language`), au premier passage.
 *  5. Le français.
 *
 * Les pages à jeton ajoutent un cran entre 2 et 3 : la langue du projet,
 * posée par `ResolveAccessToken` une fois le jeton lu — la personne qui
 * organise sait mieux qu'un en-tête dans quelle langue sa mère répondra.
 *
 * Le témoin est **fonctionnel**, pas de mesure : il ne demande pas de
 * consentement, et il n'est posé que quand il porte une information —
 * jamais pour dire « français » à un visiteur qui n'a rien choisi.
 */
final class SetLocale
{
    public const COOKIE = 'locale';

    private const ONE_YEAR_IN_MINUTES = 60 * 24 * 365;

    public function handle(Request $request, Closure $next): Response
    {
        $fromUrl = LocalizedRoutes::localeOf($request->route());
        $remembered = Locale::tryFromTag($request->cookie(self::COOKIE));

        Locales::set($fromUrl ?? $remembered ?? self::preferred($request));

        $response = $next($request);

        // L'adresse a décidé : on s'en souvient pour les pages sans préfixe
        // (l'espace, les écritures du tunnel). Sauf le français par défaut
        // pour qui n'a rien choisi : un témoin de plus pour ne rien dire.
        if ($fromUrl !== null && $fromUrl !== $remembered && ($remembered !== null || $fromUrl !== Locale::default())) {
            $response->headers->setCookie(self::cookie($fromUrl));
        }

        return $response;
    }

    /**
     * La langue que porte le **chemin**, pour les réponses qui n'ont pas de
     * route : une adresse qui n'existe pas.
     *
     * Une page d'erreur ne traverse pas le groupe « web » — il n'y a pas de
     * route à laquelle l'accrocher —, donc ni cet intergiciel, ni la session,
     * ni le témoin déchiffré. Il reste le chemin, et il suffit : quelqu'un
     * qui se trompe d'adresse sous `/it/` lit l'italien.
     */
    public static function fromPath(Request $request): ?Locale
    {
        return Locale::fromUrlPrefix((string) $request->segment(1));
    }

    /** La langue sans adresse ni témoin : le compte, puis le navigateur. */
    public static function preferred(Request $request): Locale
    {
        $user = $request->user();
        $account = $user?->locale;

        if ($account instanceof Locale) {
            return $account;
        }

        return Locale::fromAcceptLanguage($request->header('Accept-Language'))
            ?? Locale::default();
    }

    /** Un choix explicite a été fait, par l'adresse ou le sélecteur. */
    public static function hasExplicitChoice(Request $request): bool
    {
        return Locale::tryFromTag($request->cookie(self::COOKIE)) !== null;
    }

    public static function cookie(Locale $locale): Cookie
    {
        return cookie(
            name: self::COOKIE,
            value: $locale->value,
            minutes: self::ONE_YEAR_IN_MINUTES,
            secure: (bool) config('session.secure'),
            httpOnly: true,
            sameSite: 'lax',
        );
    }
}
