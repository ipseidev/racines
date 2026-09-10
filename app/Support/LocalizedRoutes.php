<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\Locale;
use Closure;
use Illuminate\Routing\Route as RouteInstance;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Route;

/**
 * Les pages publiques ont une adresse par langue.
 *
 * `/comment-ca-marche` en français, `/it/come-funziona` en italien,
 * `/fr-ch/comment-ca-marche` en français de Suisse : c'est ce que Google
 * attend pour indexer chaque version (T-238), et ce que dit `hreflang`.
 *
 * Les routes sont enregistrées **une fois par locale** avec le même corps :
 * le français à la racine sous ses noms d'origine (`home`, `checkout.show`…),
 * les autres sous un préfixe d'adresse et de nom (`it.home`,
 * `fr-ch.checkout.show`). Les segments viennent de `lang/{langue}/routes.php`.
 *
 * Ce qui n'est **pas** décliné : l'espace, les comptes, les pages à jeton, et
 * les écritures du tunnel (`POST /acheter/etape/…`) — une adresse, la même
 * dans toutes les langues ; leur langue vient du témoin ou du compte
 * (`SetLocale`).
 */
final class LocalizedRoutes
{
    /**
     * Les noms de base des pages déclinées, dans l'ordre du plan de site.
     *
     * @var list<string>
     */
    public const PAGES = [
        'home',
        'how_it_works',
        'books',
        'faq',
        'demo',
        'legal.terms',
        'legal.privacy',
        'legal.imprint',
        'legal.consents',
        'checkout.show',
        'checkout.thanks',
    ];

    /**
     * Enregistre un même corps de routes pour chaque locale.
     *
     * @param  Closure(Locale): void  $pages  Déclare les pages avec `self::uri()`.
     */
    public static function register(Closure $pages): void
    {
        foreach (Locale::cases() as $locale) {
            $prefix = $locale->urlPrefix();

            if ($prefix === null) {
                $pages($locale);

                continue;
            }

            Route::prefix($prefix)->name($prefix.'.')->group(fn () => $pages($locale));
        }
    }

    /** Le segment d'adresse d'une page dans une locale : `come-funziona`. */
    public static function uri(string $name, Locale $locale): string
    {
        $slug = Lang::get('routes.'.$name, [], $locale->language());

        if (! is_string($slug) || $slug === '' || $slug === 'routes.'.$name) {
            throw new \RuntimeException("Aucun segment d'adresse pour la page [{$name}] en [{$locale->language()}] (lang/{$locale->language()}/routes.php).");
        }

        return $slug;
    }

    /**
     * Sépare un nom de route en sa locale et son nom de base :
     * `it.checkout.show` → [Locale::Italian, 'checkout.show'].
     *
     * @return array{0: Locale, 1: string}
     */
    public static function split(string $name): array
    {
        foreach (Locale::cases() as $locale) {
            $prefix = $locale->routePrefix();

            if ($prefix !== '' && str_starts_with($name, $prefix)) {
                return [$locale, substr($name, strlen($prefix))];
            }
        }

        return [Locale::default(), $name];
    }

    /** Le nom de base d'une route, sans son préfixe de locale. */
    public static function baseName(?string $name): ?string
    {
        return $name === null ? null : self::split($name)[1];
    }

    /**
     * La locale que porte l'adresse d'une page publique déclinée, ou `null`
     * pour toute autre route — dont la langue ne se lit pas dans l'adresse.
     */
    public static function localeOf(?RouteInstance $route): ?Locale
    {
        $name = $route?->getName();

        if ($name === null) {
            return null;
        }

        [$locale, $base] = self::split($name);

        return in_array($base, self::PAGES, true) ? $locale : null;
    }

    /**
     * L'adresse d'une page publique dans une locale (la courante par défaut).
     *
     * @param  array<string, mixed>  $parameters
     */
    public static function route(string $name, array $parameters = [], ?Locale $locale = null, bool $absolute = true): string
    {
        $locale ??= Locales::current();

        return route($locale->routePrefix().$name, $parameters, $absolute);
    }

    /**
     * La même page dans chaque langue — pour `hreflang` et le sélecteur.
     *
     * Vide hors des pages déclinées : l'espace d'un compte n'a qu'une adresse.
     *
     * @return array<string, string> locale → adresse absolue
     */
    public static function alternates(?RouteInstance $route): array
    {
        if (self::localeOf($route) === null || $route === null) {
            return [];
        }

        $base = self::split((string) $route->getName())[1];
        $parameters = $route->parameters();
        $alternates = [];

        foreach (Locale::cases() as $locale) {
            $alternates[$locale->value] = self::route($base, $parameters, $locale);
        }

        return $alternates;
    }

    /**
     * Les adresses des pages publiques dans une locale, pour le front.
     *
     * Relatives : elles se posent dans un `<Link>` comme le faisaient les
     * adresses écrites en dur. La clé remplace le point par un tiret bas
     * (`legal.terms` → `legal_terms`), pour se lire comme une propriété.
     *
     * @return array<string, string>
     */
    public static function urls(?Locale $locale = null): array
    {
        $urls = [];

        foreach (self::PAGES as $name) {
            $urls[str_replace('.', '_', $name)] = self::route($name, [], $locale, false);
        }

        return $urls;
    }

    /**
     * Les chemins de toutes les pages déclinées, dans toutes les langues,
     * sans barre initiale et avec `/` pour l'accueil : le format de
     * `Request::is()`.
     *
     * @return list<string>
     */
    public static function paths(): array
    {
        $paths = [];

        foreach (Locale::cases() as $locale) {
            foreach (self::PAGES as $name) {
                $path = trim(self::route($name, [], $locale, false), '/');
                $paths[] = $path === '' ? '/' : $path;
            }
        }

        return $paths;
    }
}
