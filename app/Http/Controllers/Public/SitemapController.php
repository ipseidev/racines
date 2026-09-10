<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Enums\Locale;
use App\Support\LocalizedRoutes;
use App\Support\Seo;
use Illuminate\Http\Response;

/**
 * Le plan de site : neuf pages, cinq langues.
 *
 * Chaque adresse déclare ses sœurs par `xhtml:link` — c'est la forme que
 * Google demande pour comprendre que `/cgv` et `/it/condizioni-di-vendita`
 * sont la même page, et la seule qui vaille dans un plan de site (les
 * `hreflang` de l'en-tête HTML disent la même chose, les deux se confirment).
 * Chaque groupe se déclare **lui-même** en plus des autres : sans la ligne
 * réflexive, Google ignore le groupe entier.
 */
final class SitemapController
{
    public function __invoke(): Response
    {
        $lines = ['<?xml version="1.0" encoding="UTF-8"?>'];
        $lines[] = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">';

        foreach (Seo::SITEMAP as $name) {
            $alternates = [];

            foreach (Locale::cases() as $locale) {
                $alternates[$locale->value] = LocalizedRoutes::route($name, [], $locale);
            }

            $default = LocalizedRoutes::route($name, [], Locale::default());

            foreach ($alternates as $href) {
                $lines[] = '    <url>';
                $lines[] = '        <loc>'.htmlspecialchars($href, ENT_XML1).'</loc>';

                foreach ($alternates as $code => $alternate) {
                    $lines[] = '        <xhtml:link rel="alternate" hreflang="'.$code.'" href="'.htmlspecialchars($alternate, ENT_XML1).'"/>';
                }

                $lines[] = '        <xhtml:link rel="alternate" hreflang="x-default" href="'.htmlspecialchars($default, ENT_XML1).'"/>';
                // La priorité est relative : l'accueil d'abord, les pages de
                // vente ensuite, les textes légaux en dernier. Google la lit
                // peu, mais elle dit notre hiérarchie, et c'est elle qu'on
                // veut voir reprise.
                $lines[] = '        <priority>'.self::priority($name).'</priority>';
                $lines[] = '    </url>';
            }
        }

        $lines[] = '</urlset>';

        return response(implode("\n", $lines), 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }

    private static function priority(string $name): string
    {
        return match (true) {
            $name === 'home' => '1.0',
            str_starts_with($name, 'legal.') => '0.3',
            default => '0.8',
        };
    }
}
