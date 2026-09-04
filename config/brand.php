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
    'tagline' => 'Le livre de souvenirs de vos parents qui va réellement au bout.',
    'links_domain' => env('LINKS_DOMAIN', parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST)),
    'support_email' => env('BRAND_SUPPORT_EMAIL', 'support@example.test'),
    'support_phone' => env('BRAND_SUPPORT_PHONE'),
    'sms_sender_id' => env('BRAND_SMS_SENDER_ID', 'PRODUCT'),

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
