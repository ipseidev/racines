<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Marque
|--------------------------------------------------------------------------
|
| Valeurs de repli uniquement. La source de vérité est BrandSettings (bloc 01),
| éditable dans l'administration sans déploiement. Le nom, le domaine des liens
| et les couleurs ne doivent apparaître nulle part ailleurs dans le code.
|
*/

return [
    'product_name' => env('BRAND_PRODUCT_NAME', 'Product'),
    'short_name' => env('BRAND_SHORT_NAME', 'Product'),
    'tagline' => 'Le livre de leurs souvenirs, avec leur voix à chaque page.',
    'links_domain' => env('LINKS_DOMAIN', parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST)),
    'support_email' => env('BRAND_SUPPORT_EMAIL', 'support@example.test'),
    'support_phone' => env('BRAND_SUPPORT_PHONE'),
    'sms_sender_id' => env('BRAND_SMS_SENDER_ID', 'PRODUCT'),

    /*
     * La marque figurée : le pictogramme posé à côté du nom dans les en-têtes,
     * et l'icône des onglets. Deux fichiers servis depuis public/, repli d'un
     * `mark_path` téléversé dans l'administration — comme les couleurs, la
     * configuration est le repli et la base la source de vérité.
     */
    'mark' => '/img/brand/mark.svg',

    /*
     * Le même pictogramme, en PNG, pour les courriels : Gmail et Outlook ne
     * dessinent pas un SVG. Généré depuis mark.svg (192 px de large, soit
     * quatre fois son affichage). Si l'administration téléverse un
     * pictogramme matriciel, il le remplace ; si elle téléverse un SVG, les
     * courriels portent le nom seul plutôt qu'un dessin qui ne serait plus
     * le sien.
     */
    'mark_email' => '/img/brand/mark.png',

    /*
     * Palette issue de l'analyse colorimétrique du fondateur (3 septembre 2026,
     * docs/design/README.md). Deux couleurs signature : le vert forêt, qui
     * porte la marque, et la terracotta, qui porte l'action — et rien d'autre.
     * Un bouton se voit par son isolement, pas par sa teinte : la terracotta
     * est la seule couleur chaude saturée d'une page.
     */
    'colors' => [
        'primary' => '#2F4A3F',
        'primary_foreground' => '#FFFFFF',
        'accent' => '#B0432A',
        'accent_foreground' => '#FFFFFF',
        'background' => '#FBF6EE',
        'surface' => '#FFFFFF',
        'text' => '#26211C',
        'muted' => '#5A5049',
    ],

    'fonts' => [
        'display' => 'Fraunces',
        'body' => 'Inter',
    ],

    'legal_entity' => env('BRAND_LEGAL_ENTITY', ''),
    'legal_address' => env('BRAND_LEGAL_ADDRESS', ''),
];
