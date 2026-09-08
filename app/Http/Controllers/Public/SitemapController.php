<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Support\Seo;
use Illuminate\Http\Response;

/**
 * Le plan de site (T-225).
 *
 * Il ne fait pas remonter une page dans les résultats : il dit à Google
 * quelles pages existent et lesquelles comptent. C'est ce qui manquait pour
 * que les six pages du site soient explorées comme un ensemble, et un ensemble
 * exploré est la condition des liens de site sous un résultat de marque.
 *
 * Écrit à la main plutôt qu'avec un paquet : neuf adresses fixes, aucune ne
 * vient de la base, et une dépendance de plus se maintient.
 *
 * Les pages à jeton, l'espace et le tunnel n'y sont pas — les deux premières
 * n'ont rien à donner à lire, le troisième est en `noindex`.
 */
final class SitemapController
{
    public function __invoke(): Response
    {
        $lines = ['<?xml version="1.0" encoding="UTF-8"?>'];
        $lines[] = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        foreach (array_keys(Seo::SITEMAP) as $path) {
            $lines[] = '    <url>';
            $lines[] = '        <loc>'.htmlspecialchars(url($path), ENT_XML1).'</loc>';
            // La priorité est relative : l'accueil d'abord, les pages de vente
            // ensuite, les textes légaux en dernier. Google la lit peu, mais
            // elle dit notre hiérarchie, et c'est elle qu'on veut voir reprise.
            $lines[] = '        <priority>'.($path === '/' ? '1.0' : (str_starts_with($path, '/c') || str_starts_with($path, '/m') ? '0.3' : '0.8')).'</priority>';
            $lines[] = '    </url>';
        }

        $lines[] = '</urlset>';

        return response(implode("\n", $lines), 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }
}
