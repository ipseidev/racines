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
        // Les numéros autorisés à recevoir un vrai SMS **hors production**,
        // séparés par des virgules. Vide, rien ne part : le décor sème des
        // mobiles français plausibles, et un SMS ne se décommande pas (T-174).
        'allowlist' => env('SMS_ALLOWLIST', ''),
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
     * `clamav`, `fake` ou `off`, jamais déduit de l'environnement (T-61) : un
     * fournisseur déduit finit par être le faux en production. `off`
     * débranche le contrôle en le journalisant — décision D-12, à ne pas
     * confondre avec `fake`, qui simule un scanner (T-216).
     */
    /*
     * Le rendu du BAT (bloc 13).
     *
     * Trois chemins, tous facultatifs : l'image de développement, l'intégration
     * continue et le serveur ne placent pas Node et Chromium au même endroit,
     * et Browsershot sait les trouver seul quand ils sont dans le `PATH`. Les
     * renseigner sert aux environnements où ils n'y sont pas.
     */
    /*
     * PostHog, cloud **UE** (bloc 15).
     *
     * Le pilote choisit son fournisseur, jamais son environnement : `driver`
     * décide, et une clé présente ne suffit pas à activer l'envoi. La leçon
     * T-61 vaut ici plus qu'ailleurs — une clé oubliée dans un `.env` de
     * développement ferait partir les événements d'un décor vers le projet de
     * production, et les chiffres du pilote mentiraient sans que rien ne le
     * dise.
     */
    'posthog' => [
        'driver' => env('ANALYTICS_DRIVER', 'log'),
        'key' => env('POSTHOG_KEY'),
        'host' => env('POSTHOG_HOST', 'https://eu.i.posthog.com'),
    ],

    /*
     * Google Analytics 4, la mesure du **site marchand**.
     *
     * Elle ne remplace pas PostHog et ne la recoupe pas : PostHog mesure
     * l'entonnoir du produit — trente points, par cohorte, avec le projet en
     * propriété (`App\Analytics\Track`) — tandis que GA mesure l'audience
     * des pages publiques, ce qui amène du monde et ce que ce monde devient.
     * Les deux vivent aux mêmes endroits : les pages publiques et l'espace de
     * l'Initiateur·rice, **jamais** un espace à jeton (`App\Analytics\Measured`).
     *
     * Le même verrou qu'ailleurs, et pour la même raison (T-61) : c'est
     * `GA_ENABLED` qui décide, jamais la présence de l'identifiant. Un
     * identifiant laissé dans un `.env` de développement enverrait les visites
     * d'un décor dans la propriété de production, et le taux de conversion de
     * la page d'accueil mentirait sans que rien ne le dise. L'identifiant
     * n'est pas un secret — il est lisible dans la source de la page — mais
     * il ne se code pas en dur : le site tourne sur plusieurs domaines, et
     * une préproduction ne mesure pas dans la propriété du site.
     */
    'google_analytics' => [
        'enabled' => env('GA_ENABLED', false),
        'measurement_id' => env('GA_MEASUREMENT_ID'),
    ],

    /*
     * Le pixel Meta, la mesure de la **publicité** (T-226).
     *
     * Elle ne recoupe ni PostHog ni Google Analytics : les deux premières
     * disent ce que font les visiteurs, celle-ci dit à Meta lesquels ont
     * acheté, pour qu'il apprenne à qui montrer l'annonce. Sans l'achat
     * renvoyé, une campagne ne peut s'optimiser que sur la vue de page — ce
     * qui, pour un cadeau à quatre-vingt-neuf euros décidé en plusieurs
     * visites, revient à acheter du trafic au hasard.
     *
     * Deux moitiés, et la seconde est la plus importante. Le **pixel** dans le
     * navigateur mesure l'intention (arrivée sur la page, clic vers le
     * tunnel) ; l'**API de conversions**, appelée par le serveur depuis le
     * webhook Stripe, mesure l'achat. C'est la seule qui survit à un bloqueur
     * de publicité, à Safari et à un onglet fermé pendant le paiement — et
     * c'est l'achat qui compte.
     *
     * Le verrou de T-61, comme partout : c'est `META_PIXEL_ENABLED` qui
     * décide, jamais la présence de l'identifiant. Un identifiant laissé dans
     * un `.env` de développement enverrait les commandes d'un décor dans le
     * compte publicitaire de production, et le coût d'acquisition mentirait
     * sans que rien ne le dise.
     *
     * `test_code` n'est renseigné que le temps de vérifier le branchement
     * dans le testeur d'événements de Meta : laissé en place, les événements
     * n'entrent jamais dans l'optimisation.
     */
    'meta' => [
        'enabled' => env('META_PIXEL_ENABLED', false),
        'pixel_id' => env('META_PIXEL_ID'),
        // Le jeton de l'API de conversions. Un secret, contrairement à
        // l'identifiant du pixel, qui est lisible dans la source de la page.
        'capi_token' => env('META_CAPI_TOKEN'),
        'api_version' => env('META_API_VERSION', 'v21.0'),
        'test_code' => env('META_TEST_EVENT_CODE'),
    ],

    'browsershot' => [
        // `browsershot` ou `fake`, jamais déduit de l'environnement (T-61) :
        // un rendu déduit finit par être le faux en production, et une famille
        // recevrait un PDF d'une page blanche.
        'driver' => env('PDF_DRIVER', 'browsershot'),
        'node_path' => env('BROWSERSHOT_NODE_PATH'),
        'npm_path' => env('BROWSERSHOT_NPM_PATH'),
        'chrome_path' => env('BROWSERSHOT_CHROME_PATH'),
    ],

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
        // Le coupon de l'offre de bienvenue (T-141) : un pourcentage sur la
        // commande, créé dans Stripe, le même que le réglage du pilote.
        'coupons' => [
            'welcome' => env('STRIPE_COUPON_WELCOME'),
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
