<?php

declare(strict_types=1);

use App\Health\AuditChainCheck;
use App\Health\ClamavCheck;
use App\Health\R2ReachableCheck;
use App\Health\ReplicationLagCheck;
use Spatie\Health\Checks\Checks\CacheCheck;
use Spatie\Health\Checks\Checks\DatabaseCheck;
use Spatie\Health\Checks\Checks\HorizonCheck;
use Spatie\Health\Checks\Checks\RedisCheck;
use Spatie\Health\Checks\Checks\ScheduleCheck;
use Spatie\Health\Checks\Checks\UsedDiskSpaceCheck;
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
        'only_on_failure' => false,

        'mail' => [
            'to' => env('HEALTH_TO_ADDRESS', ''),

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
     * Les contrôles, et l'ordre dans lequel on veut les lire.
     *
     * Les quatre derniers sont propres à ce produit et disent chacun une
     * promesse du dossier : le stockage porte les voix, le journal d'audit
     * porte la preuve, l'antivirus garde la porte des photos, et la
     * réplication tient l'engagement de non-perte. Les contrôles génériques
     * (base, cache, file, disque) disent seulement que la machine tourne.
     */
    'checks' => [
        DatabaseCheck::new(),
        CacheCheck::new(),
        RedisCheck::new(),
        HorizonCheck::new(),
        // Le planificateur bat toutes les minutes ; dix minutes de silence
        // veulent dire que les relances, les envois et les sauvegardes se
        // sont arrêtés sans que rien ne le dise.
        ScheduleCheck::new()->heartbeatMaxAgeInMinutes(10),
        UsedDiskSpaceCheck::new()->warnWhenUsedSpaceIsAbovePercentage(80),

        R2ReachableCheck::new(),
        AuditChainCheck::new(),
        ClamavCheck::new(),
        ReplicationLagCheck::new(),
    ],
];
