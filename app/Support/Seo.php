<?php

declare(strict_types=1);

namespace App\Support;

use App\Settings\PilotSettings;
use Illuminate\Support\Facades\Lang;

/**
 * Le titre, la description et l'indexation de chaque page publique (T-225).
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
 */
final class Seo
{
    /**
     * Composant Inertia vers sa clé de catalogue et son indexation.
     *
     * Les pages absentes de cette table ne reçoivent ni description ni
     * canonique : ce sont les pages à jeton et l'espace, qui n'ont rien à
     * donner à lire à un moteur.
     *
     * @var array<string, array{key: string, indexable: bool}>
     */
    private const PAGES = [
        'public/Landing' => ['key' => 'home', 'indexable' => true],
        'public/HowItWorks' => ['key' => 'how', 'indexable' => true],
        'public/Books' => ['key' => 'books', 'indexable' => true],
        'public/Faq' => ['key' => 'faq', 'indexable' => true],
        'public/Demo' => ['key' => 'demo', 'indexable' => true],
        'public/Legal' => ['key' => 'legal', 'indexable' => true],
        'public/Consents' => ['key' => 'consents', 'indexable' => true],
        // Le tunnel et le remerciement : suivis, jamais indexés. Une étape de
        // paiement dans les résultats de recherche n'aide personne.
        'public/Checkout' => ['key' => 'checkout', 'indexable' => false],
        'public/CheckoutThanks' => ['key' => 'checkout', 'indexable' => false],
        // Le témoin du test : hors index, sans quoi il se disputerait le
        // trafic de l'accueil, qui sert la même offre (T-219).
        'public/LandingTemoin' => ['key' => 'home', 'indexable' => false],
    ];

    /**
     * Les pages qui entrent au plan de site, dans l'ordre d'importance.
     *
     * @var array<string, string>
     */
    public const SITEMAP = [
        '/' => 'home',
        '/comment-ca-marche' => 'how',
        '/nos-livres' => 'books',
        '/questions-frequentes' => 'faq',
        '/essai' => 'demo',
        '/cgv' => 'legal',
        '/confidentialite' => 'legal',
        '/mentions-legales' => 'legal',
        '/consentements' => 'legal',
    ];

    /**
     * Les quatre pages légales sortent d'un seul composant : sans le chemin,
     * elles partageaient un titre et une description, ce qui est exactement le
     * défaut qu'on corrige ici.
     *
     * @var array<string, string>
     */
    private const BY_PATH = [
        'cgv' => 'terms',
        'confidentialite' => 'privacy',
        'mentions-legales' => 'imprint',
    ];

    /**
     * Les pages qui désignent une autre adresse comme canonique.
     *
     * Le témoin du test sert la même offre que l'accueil : sans cette ligne,
     * deux adresses se disputeraient le même trafic (T-219).
     *
     * @var array<string, string>
     */
    private const CANONICAL = [
        'public/LandingTemoin' => '/',
    ];

    /**
     * @return array{title: string, description: string, indexable: bool, brand: bool, canonical: string}
     */
    public static function forComponent(string $component, string $path = ''): array
    {
        $page = self::PAGES[$component] ?? null;

        if ($page === null) {
            return [
                'title' => '',
                'description' => '',
                'indexable' => false,
                'brand' => false,
                'canonical' => '',
            ];
        }

        $key = self::BY_PATH[trim($path, '/')] ?? $page['key'];
        $canonical = self::CANONICAL[$component] ?? null;

        return [
            'title' => self::line($key, 'title'),
            'description' => self::line($key, 'description'),
            'indexable' => $page['indexable'],
            // La page de marque : son titre porte déjà le nom, on ne le
            // suffixe pas une seconde fois.
            'brand' => $key === 'home',
            'canonical' => $canonical === null ? url()->current() : url($canonical),
        ];
    }

    /**
     * Les données structurées de la page, en JSON-LD.
     *
     * Trois choses, et pas une de plus. L'**organisation**, pour que le nom de
     * marque désigne une entité et non une suite de lettres — c'est ce qui
     * rattache les liens de site à un résultat de marque. Le **site**, pour
     * lier le nom au domaine. Et sur les deux pages qui vendent, le
     * **produit** avec son offre, prix lu dans les réglages.
     *
     * Ce qu'on n'y met pas, et c'est délibéré : aucune note agrégée. La page
     * affiche « 4,9 » sur décision du fondateur, mais une note déclarée en
     * donnée structurée sans avis vérifiables se paie d'une action manuelle,
     * et celle-ci coûte tout le référencement de marque, pas seulement
     * l'étoile.
     *
     * @return list<array<string, mixed>>
     */
    public static function jsonLd(string $component): array
    {
        $page = self::PAGES[$component] ?? null;

        if ($page === null) {
            return [];
        }

        $brand = Brand::nameSafe();
        $home = url('/');

        $graph = [
            [
                '@context' => 'https://schema.org',
                '@type' => 'Organization',
                '@id' => $home.'#organisation',
                'name' => $brand,
                'url' => $home,
                'email' => Brand::supportEmail(),
                'logo' => url('/favicon.svg'),
            ],
            [
                '@context' => 'https://schema.org',
                '@type' => 'WebSite',
                '@id' => $home.'#site',
                'name' => $brand,
                'url' => $home,
                'inLanguage' => 'fr-FR',
                'publisher' => ['@id' => $home.'#organisation'],
            ],
        ];

        if (in_array($page['key'], ['home', 'books'], true)) {
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
                    'priceCurrency' => 'EUR',
                    'availability' => 'https://schema.org/InStock',
                    'url' => url('/acheter'),
                ],
            ];
        }

        return $graph;
    }

    /**
     * Une ligne du catalogue, avec le nom de marque et le prix substitués.
     *
     * Le prix vient des réglages : une description qui l'écrirait en dur
     * mentirait le jour où il change, et c'est la ligne que Google affiche.
     */
    private static function line(string $key, string $field): string
    {
        $value = Lang::get("public.seo.{$key}.{$field}", [
            'brand' => Brand::nameSafe(),
            'price' => Money::euros(app(PilotSettings::class)->pilot_price_cents),
        ]);

        return is_string($value) ? $value : '';
    }
}
