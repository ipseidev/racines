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

    'colors' => [
        'primary' => '#1F3D2B',
        'primary_foreground' => '#FFFFFF',
        'accent' => '#D9E76C',
        'accent_foreground' => '#1F3D2B',
        'background' => '#F7F5EF',
        'surface' => '#FFFFFF',
        'text' => '#1B1B1B',
        'muted' => '#6B6B6B',
    ],

    'fonts' => [
        'display' => 'Fraunces',
        'body' => 'Inter',
    ],

    'legal_entity' => env('BRAND_LEGAL_ENTITY', ''),
    'legal_address' => env('BRAND_LEGAL_ADDRESS', ''),
];
