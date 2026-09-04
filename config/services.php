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
    | Transcription et mise au propre
    |--------------------------------------------------------------------------
    |
    | `fake` dans la suite de tests, forcé par `phpunit.xml`. Gladia est le
    | fournisseur ASR par défaut (hébergement UE, T-07) ; Deepgram est le
    | second adaptateur et le point de comparaison du banc d'essai.
    |
    */

    /*
     * Stripe. Les identifiants de prix vivent ici et non en dur dans le
     * tunnel : ils diffèrent entre le mode test et le mode live, et une
     * constante oubliée ferait facturer le mauvais montant.
     */
    /*
     * Le contrôle antivirus des fichiers déposés (bloc 12).
     *
     * `clamav` ou `fake`, jamais déduit de l'environnement (T-61) : un
     * fournisseur déduit finit par être le faux en production.
     */
    'antivirus' => [
        'scanner' => env('ANTIVIRUS_SCANNER', 'clamav'),
        'host' => env('CLAMAV_HOST', 'clamav'),
        'port' => env('CLAMAV_PORT', 3310),
    ],

    'stripe' => [
        // `stripe` ou `fake` ; jamais déduit de l'environnement (leçon T-61).
        'driver' => env('STRIPE_DRIVER', 'stripe'),
        'prices' => [
            'pilot' => env('STRIPE_PRICE_PILOT'),
            'prevente_99' => env('STRIPE_PRICE_PREVENTE_99'),
            'prevente_129' => env('STRIPE_PRICE_PREVENTE_129'),
            'extra_copy' => env('STRIPE_PRICE_EXTRA_COPY'),
            'phone_option' => env('STRIPE_PRICE_PHONE_OPTION'),
            'ebook' => env('STRIPE_PRICE_EBOOK'),
        ],
    ],

    'asr' => [
        'provider' => env('ASR_PROVIDER', 'fake'),
        'gladia_key' => env('GLADIA_API_KEY'),
        'deepgram_key' => env('DEEPGRAM_API_KEY'),
        // On signe nous-mêmes les URL de rappel : les fournisseurs ne signent
        // pas tous les leurs, et un faux rappel injecterait une fausse
        // transcription dans l'histoire de quelqu'un.
        'callback_secret' => env('ASR_CALLBACK_SECRET'),
    ],

    'anthropic' => [
        'provider' => env('LLM_PROVIDER', 'fake'),
        'key' => env('ANTHROPIC_API_KEY'),
        'model' => env('LLM_MODEL', 'claude-opus-5'),
        'effort' => env('LLM_EFFORT', 'medium'),
        'max_tokens' => (int) env('LLM_MAX_TOKENS', 8000),
    ],

    'twilio' => [
        'sid' => env('TWILIO_ACCOUNT_SID'),
        'token' => env('TWILIO_AUTH_TOKEN'),
        // Numéro de repli, utilisé là où l'expéditeur alphanumérique est
        // refusé par l'opérateur (doc 04 §9, règle de décision du bloc 05).
        'from' => env('TWILIO_FROM'),
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
        // Secret Svix des webhooks de livraison (bloc 05 §6.5).
        'webhook_secret' => env('RESEND_WEBHOOK_SECRET'),
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
