<?php

declare(strict_types=1);

use Spatie\Health\Notifications\CheckFailedNotification;
use Spatie\Health\Notifications\Notifiable;
use Spatie\Health\ResultStores\CacheHealthResultStore;

return [
    /*
     * A result store is responsible for saving the results of the checks. The
     * `EloquentHealthResultStore` will save results in the database. You
     * can use multiple stores at the same time.
     */
    /*
     * Le cache, et non une table.
     *
     * Le paquet propose de garder l'historique en base. Ici il ne servirait
     * personne : c'est **Oh Dear** qui interroge `/health` et qui garde
     * l'historique, avec ses courbes et ses alertes. Une table de plus dans
     * la base de production, qui grossit à chaque minute et qu'il faudrait
     * élaguer, pour une donnée qu'un autre outil détient déjà, ne se
     * justifie pas.
     *
     * Conséquence assumée : un redémarrage du cache efface le dernier
     * résultat connu. Sans importance — la question posée à `/health` est
     * « comment ça va **maintenant** », et la réponse se recalcule.
     */
    'result_stores' => [
        CacheHealthResultStore::class => [
            'store' => env('HEALTH_CACHE_STORE', 'redis'),
        ],
    ],

    /*
     * You can get notified when specific events occur. Out of the box you can use 'mail' and 'slack'.
     * For Slack you need to install laravel/slack-notification-channel.
     */
    /*
     * L'alerte, sans service externe payant (T-201).
     *
     * Oh Dear était prévu par la feuille du bloc ; il est écarté sur son
     * coût. Ce qu'il faisait se sépare en deux, et les deux moitiés se
     * remplacent séparément :
     *
     *  - **« un contrôle est au rouge »** : c'est ici. Le planificateur passe
     *    toutes les minutes, et un échec part par courriel au fondateur,
     *    limité à un message par heure. Aucun service tiers, aucune donnée
     *    qui sort.
     *  - **« le serveur ne répond plus »** : cela ne peut pas venir du
     *    serveur lui-même — s'il est tombé, il ne prévient personne. Il faut
     *    un appel depuis l'extérieur sur `/up`, la route de Laravel, qui
     *    n'expose rien. Un service gratuit suffit, c'est une ligne à
     *    configurer et rien à écrire ici (voir `docs/runbooks/supervision.md`).
     */
    'notifications' => [
        /*
         * Notifications will only get sent if this option is set to `true`.
         */
        'enabled' => env('HEALTH_NOTIFICATIONS_ENABLED', true),

        'notifications' => [
            CheckFailedNotification::class => ['mail'],
        ],

        /*
         * Here you can specify the notifiable to which the notifications should be sent. The default
         * notifiable will use the variables specified in this config file.
         */
        'notifiable' => Notifiable::class,

        /*
         * When checks start failing, you could potentially end up getting
         * a notification every minute.
         *
         * With this setting, notifications are throttled. By default, you'll
         * only get one notification per hour.
         */
        'throttle_notifications_for_minutes' => 60,
        'throttle_notifications_key' => 'health:latestNotificationSentAt:',

        /*
         * When set to true, notifications will only be sent when at least one
         * check has a 'failed' status. Warnings will be ignored.
         */
        /*
         * Les avertissements aussi, pas seulement les échecs.
         *
         * Un disque à 80 % et un stockage qui répond en trois secondes ne
         * sont pas des pannes : ce sont les deux heures qu'on a pour agir
         * avant d'en avoir une. Le courriel étant limité à un par heure, le
         * risque de lassitude est faible.
         */
        'only_on_failure' => false,

        'mail' => [
            /*
             * L'adresse du support à défaut d'une adresse dédiée : c'est
             * celle que le fondateur relève, et une alerte envoyée à une
             * boîte que personne n'ouvre est une alerte perdue. Le nom de
             * marque n'apparaît pas en dur — il vient des réglages.
             */
            'to' => env('HEALTH_TO_ADDRESS') ?: env('BRAND_SUPPORT_EMAIL', ''),

            'from' => [
                'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
                'name' => env('MAIL_FROM_NAME', 'Example'),
            ],
        ],

        'slack' => [
            'webhook_url' => env('HEALTH_SLACK_WEBHOOK_URL', ''),

            /*
             * If this is set to null the default channel of the webhook will be used.
             */
            'channel' => null,

            'username' => null,

            'icon' => null,
        ],
    ],

    /*
     * You can let Oh Dear monitor the results of all health checks. This way, you'll
     * get notified of any problems even if your application goes totally down. Via
     * Oh Dear, you can also have access to more advanced notification options.
     */
    'oh_dear_endpoint' => [
        /*
         * Ouvert dès qu'un secret existe, fermé sinon.
         *
         * Un drapeau séparé du secret finit toujours par diverger : endpoint
         * activé sans secret — la liste des services, leurs temps de réponse
         * et l'état de la file, offerts à qui passe — ou secret posé sans
         * endpoint, et une supervision qui ne supervise rien. Ici l'un
         * implique l'autre.
         */
        'enabled' => (string) env('OH_DEAR_HEALTH_CHECK_SECRET', '') !== '',

        /*
         * When this option is enabled, the checks will run before sending a response.
         * Otherwise, we'll send the results from the last time the checks have run.
         */
        'always_send_fresh_results' => true,

        /*
         * The secret that is displayed at the Application Health settings at Oh Dear.
         */
        'secret' => env('OH_DEAR_HEALTH_CHECK_SECRET'),

        /*
         * `/health`, et non l'adresse par défaut du paquet.
         *
         * Elle est écrite dans le runbook de déploiement, dans la
         * configuration d'Oh Dear et dans la tête de qui déboguera à trois
         * heures du matin : autant qu'elle soit prononçable. Le secret, lui,
         * la protège.
         */
        'url' => '/health',
    ],

    /*
     * You can specify a heartbeat URL for the Horizon check.
     * This URL will be pinged if the Horizon check is successful.
     * This way you can get notified if Horizon goes down.
     */
    'horizon' => [
        'heartbeat_url' => env('HORIZON_HEARTBEAT_URL'),
    ],

    /*
     * You can specify a heartbeat URL for the Schedule check.
     * This URL will be pinged if the Schedule check is successful.
     * This way you can get notified if the schedule fails to run.
     */
    'schedule' => [
        'heartbeat_url' => env('SCHEDULE_HEARTBEAT_URL'),
    ],

    /*
     * You can set a theme for the local results page
     *
     * - light: light mode
     * - dark: dark mode
     */
    'theme' => 'light',

    /*
     * When enabled, completed `HealthQueueJob`s will be displayed
     * in Horizon's silenced jobs screen.
     */
    'silence_health_queue_job' => true,

    /*
     * The response code to use for HealthCheckJsonResultsController when a health
     * check has failed
     */
    'json_results_failure_status' => 200,

    /*
     * You can specify a secret token that needs to be sent in the X-Secret-Token for secured access.
     */
    'secret_token' => env('HEALTH_SECRET_TOKEN'),

/**
 * By default, conditionally skipped health checks are treated as failures.
 * You can override this behavior by uncommenting the configuration below.
 *
 * @link https://spatie.be/docs/laravel-health/v1/basic-usage/conditionally-running-or-modifying-checks
 */
    // 'treat_skipped_as_failure' => false

    /*
     * Il n'y a pas de clé `checks` ici, et ce n'est pas un oubli.
     *
     * Le paquet ne la lit pas — le registre est `Health::checks()`, appelé
     * par `App\Providers\HealthServiceProvider`. Et un objet ne survit pas à
     * `config:cache`, qui écrit la configuration en PHP littéral : la poser
     * ici fait échouer le déploiement (T-207).
     */
];
