<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\Locale;
use App\Settings\PilotSettings;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Lang;

/**
 * Le titre, la description, la langue et l'indexation de chaque page
 * publique (T-225, étendu aux cinq langues par T-238).
 *
 * Ce qui se jouait ici avant : **une** description pour toutes les pages, et
 * un titre de document réduit au nom de la marque. Les deux sont des défauts
 * de référencement, pas des détails. Une description partagée dit à Google que
 * six pages parlent de la même chose, et un titre servi par le serveur qui ne
 * porte pas le sujet de la page prive celle-ci de son libellé dans les
 * résultats — c'est ce libellé, et lui seul, qui devient le nom d'un lien de
 * site (« sitelink ») sous le résultat de marque.
 *
 * Les liens de site ne se demandent pas : Google les choisit. Ce qu'on
 * contrôle est ce à partir de quoi il choisit — un petit nombre de pages,
 * liées depuis la navigation et le pied de page, chacune avec son titre et sa
 * description propres, et un plan de site qui les déclare.
 *
 * Le titre rendu par le serveur doit dire la même chose que celui du `<Head>`
 * Inertia, sinon le document en affiche deux à la suite : les deux lisent donc
 * la même clé de catalogue.
 *
 * **La clé vient du nom de route, pas du chemin.** `/cgv`, `/it/condizioni-di-vendita`
 * et `/es/condiciones-de-venta` sont la même page dans trois langues : le
 * chemin change, `legal.terms` non.
 */
final class Seo
{
    /**
     * Nom de route (sans préfixe de langue) vers clé de catalogue.
     *
     * Les routes absentes de cette table ne reçoivent ni description ni
     * canonique : ce sont les pages à jeton et l'espace, qui n'ont rien à
     * donner à lire à un moteur.
     *
     * @var array<string, string>
     */
    private const BY_ROUTE = [
        'home' => 'home',
        'how_it_works' => 'how',
        'books' => 'books',
        'faq' => 'faq',
        'demo' => 'demo',
        'legal.terms' => 'terms',
        'legal.privacy' => 'privacy',
        'legal.imprint' => 'imprint',
        'legal.consents' => 'consents',
        // Le tunnel et le remerciement : suivis, jamais indexés. Une étape de
        // paiement dans les résultats de recherche n'aide personne.
        'checkout.show' => 'checkout',
        'checkout.thanks' => 'checkout',
        // Le témoin du test : hors index, sans quoi il se disputerait le
        // trafic de l'accueil, qui sert la même offre (T-219).
        'lp.temoin' => 'home',
    ];

    /**
     * Les routes qui n'entrent pas dans l'index.
     *
     * @var list<string>
     */
    private const NOT_INDEXED = ['checkout.show', 'checkout.thanks', 'lp.temoin'];

    /**
     * Les pages qui désignent une autre adresse comme canonique.
     *
     * Le témoin du test sert la même offre que l'accueil : sans cette ligne,
     * deux adresses se disputeraient le même trafic (T-219).
     *
     * @var array<string, string>
     */
    private const CANONICAL = ['lp.temoin' => 'home'];

    /**
     * Les pages du plan de site, dans l'ordre d'importance. Chacune y figure
     * une fois par langue, avec ses sœurs déclarées en `hreflang`.
     *
     * @var list<string>
     */
    public const SITEMAP = [
        'home',
        'how_it_works',
        'books',
        'faq',
        'demo',
        'legal.terms',
        'legal.privacy',
        'legal.imprint',
        'legal.consents',
    ];

    /**
     * Tout ce que la vue racine doit écrire dans l'en-tête du document.
     *
     * @return array{
     *     title: string, description: string, indexable: bool, brand: bool,
     *     canonical: string, lang: string, openGraph: string,
     *     alternates: array<string, string>
     * }
     */
    public static function forPage(?Route $route): array
    {
        $locale = Locales::current();
        $name = LocalizedRoutes::baseName($route?->getName()) ?? '';
        $key = self::BY_ROUTE[$name] ?? null;

        if ($key === null) {
            return [
                'title' => '',
                'description' => '',
                'indexable' => false,
                'brand' => false,
                'canonical' => '',
                'lang' => $locale->tag(),
                'openGraph' => $locale->openGraph(),
                'alternates' => [],
            ];
        }

        $canonical = self::CANONICAL[$name] ?? null;

        return [
            'title' => self::line($key, 'title'),
            'description' => self::line($key, 'description'),
            'indexable' => ! in_array($name, self::NOT_INDEXED, true),
            // La page de marque : son titre porte déjà le nom, on ne le
            // suffixe pas une seconde fois.
            'brand' => $key === 'home',
            'canonical' => $canonical === null
                ? url()->current()
                : LocalizedRoutes::route($canonical, [], $locale),
            'lang' => $locale->tag(),
            'openGraph' => $locale->openGraph(),
            // Les autres langues de **cette** page. Vides sur le témoin et
            // sur toute route non déclinée : déclarer un `hreflang` vers une
            // adresse qui sert une autre page est pire que ne rien déclarer.
            'alternates' => LocalizedRoutes::alternates($route),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function jsonLd(?Route $route): array
    {
        $key = self::BY_ROUTE[LocalizedRoutes::baseName($route?->getName()) ?? ''] ?? null;

        if ($key === null) {
            return [];
        }

        $locale = Locales::current();
        $brand = Brand::nameSafe();
        $site = LocalizedRoutes::route('home', [], Locale::default());
        $home = LocalizedRoutes::route('home', [], $locale);

        $graph = [
            [
                '@context' => 'https://schema.org',
                '@type' => 'Organization',
                '@id' => $site.'#organisation',
                'name' => $brand,
                'url' => $site,
                'email' => Brand::supportEmail(),
                'logo' => url('/favicon.svg'),
            ],
            [
                '@context' => 'https://schema.org',
                '@type' => 'WebSite',
                '@id' => $home.'#site',
                'name' => $brand,
                'url' => $home,
                'inLanguage' => $locale->tag(),
                'publisher' => ['@id' => $site.'#organisation'],
            ],
        ];

        if (in_array($key, ['home', 'books'], true)) {
            $cents = app(PilotSettings::class)->pilot_price_cents;

            $graph[] = [
                '@context' => 'https://schema.org',
                '@type' => 'Product',
                'name' => self::line('product', 'title'),
                'description' => self::line('product', 'description'),
                'brand' => ['@type' => 'Brand', 'name' => $brand],
                'image' => url('/img/landing/livre.jpg'),
                'offers' => [
                    '@type' => 'Offer',
                    'price' => Money::decimal($cents),
                    // La devise de facturation, qui reste l'euro partout tant
                    // que la grille suisse n'est pas décidée (T-238) : une
                    // donnée structurée annonce ce qui sera débité, pas ce que
                    // le marché a l'habitude de lire.
                    'priceCurrency' => 'EUR',
                    'availability' => 'https://schema.org/InStock',
                    'url' => LocalizedRoutes::route('checkout.show', [], $locale),
                ],
            ];
        }

        return $graph;
    }

    private static function line(string $key, string $field): string
    {
        $value = Lang::get("public.seo.{$key}.{$field}", [
            'brand' => Brand::nameSafe(),
            'price' => Money::euros(app(PilotSettings::class)->pilot_price_cents),
        ]);

        return is_string($value) ? $value : '';
    }
}
