<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | SMS
    |--------------------------------------------------------------------------
    |
    | `fake` en test, `log` en local, `twilio` à partir du bloc 05.
    |
    */

    'sms' => [
        'provider' => env('SMS_PROVIDER', 'log'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Stockage des médias
    |--------------------------------------------------------------------------
    |
    | `s3` partout, `fake` seulement dans la suite de tests, où le pilote est
    | forcé par `phpunit.xml`.
    |
    | Ce choix est **explicite** et non déduit de l'environnement : en
    | intégration continue, l'application servie au bout en bout tourne avec
    | `APP_ENV=testing`, et une liaison qui interrogeait `runningUnitTests()`
    | lui donnait un stockage en mémoire — les envois partaient vers un hôte
    | qui n'existe pas.
    |
    */

    'media' => [
        'driver' => env('MEDIA_DRIVER', 's3'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
