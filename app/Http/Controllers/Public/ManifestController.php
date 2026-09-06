<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Support\Brand;
use Illuminate\Http\JsonResponse;

/**
 * Le manifeste d'application web, rendu et non déposé dans public/.
 *
 * Il porte le nom de la marque et deux de ses couleurs : le déposer en fichier
 * statique reviendrait à recopier le nom hors des réglages, ce que le reste du
 * code s'interdit (BrandAgnosticTest). Servi sans contrainte de domaine, il
 * répond donc aussi sur le domaine court des liens, où un manifeste d'une
 * autre origine serait refusé par le navigateur.
 */
final class ManifestController
{
    public function __invoke(): JsonResponse
    {
        $brand = Brand::settings();

        return response()
            ->json([
                'name' => $brand->product_name,
                'short_name' => $brand->short_name,
                'start_url' => '/',
                'display' => 'standalone',
                'theme_color' => $brand->color_background,
                'background_color' => $brand->color_background,
                'icons' => [
                    [
                        'src' => '/web-app-manifest-192x192.png',
                        'sizes' => '192x192',
                        'type' => 'image/png',
                        'purpose' => 'maskable',
                    ],
                    [
                        'src' => '/web-app-manifest-512x512.png',
                        'sizes' => '512x512',
                        'type' => 'image/png',
                        'purpose' => 'maskable',
                    ],
                ],
            ], options: JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            ->header('Content-Type', 'application/manifest+json');
    }
}
