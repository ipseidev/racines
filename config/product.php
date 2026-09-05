<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Paramètres produit
|--------------------------------------------------------------------------
|
| Toutes les valeurs chiffrées du produit. La source de vérité métier reste le
| référentiel docs/dossier/05_REFERENTIEL_GLOSSAIRE_SOURCES.md ; ce fichier ne
| fait que l'exécuter. Les seuils marqués [À CONFIRMER] attendent une donnée
| réelle (devis imprimeur, décision du comité).
|
*/

return [

    // Enregistrement navigateur (décision T-27, PRD US-01)
    'recording' => [
        'soft_warning_seconds' => 600,
        'hard_stop_seconds' => 1200,
        'max_bytes' => 209_715_200,
        'segment_milliseconds' => 5_000,
        'upload_part_bytes' => 5 * 1024 * 1024,
        'accepted_mimes' => [
            'audio/webm', 'audio/mp4', 'audio/ogg', 'audio/mpeg', 'audio/wav', 'audio/x-m4a',
        ],
    ],

    // Durées de vie des jetons (glossaire §4, doc 04 §12)
    'tokens' => [
        'record_days' => 30,
        'listen_project_months' => 12,
        'listen_story_days' => 90,
        'invitation_days' => 30,
        'action_days' => 14,
        'export_days' => 7,
        'narrator_space_days' => 30,
        'sensitive_grant_minutes' => 15,
    ],

    // Codes à usage unique (doc 04 §12)
    'otp' => [
        'length' => 6,
        'ttl_minutes' => 10,
        'max_attempts' => 5,
        'lockout_minutes' => 15,
        'max_challenges_per_hour' => 3,
    ],

    // Moteur de complétion (annexe C de la roadmap, PRD §5.3)
    'engine' => [
        'tick_cron' => '7 * * * *',
        'invitation_reminder_days' => [7, 14],
        'link_not_opened_days' => 3,
        'recording_abandoned_days' => 2,
        'recorded_not_validated_days' => 4,
        'recorded_not_validated_max_reminders' => 2,
        'validated_not_listened_days' => 5,
        'no_reaction_story_count' => 3,
        'react_suggestion_min_interval_days' => 30,
        'silence_light_question_days' => 10,
        'silence_alert_days' => 21,
        'silence_alert_min_interval_days' => 30,
        'declining_window_weeks' => 4,
        'declining_offer_min_interval_weeks' => 8,
        'initiator_max_requests_per_month' => 4,
    ],

    // Book-ready : critères de production, jamais un compte d'histoires (R-6)
    'book_ready' => [
        'min_words' => 12_000,
        'min_audio_minutes' => 90,
        'min_pages' => 60,
        'min_themes' => 5,
        'words_per_page' => 280,
    ],

    // Livre imprimé (bloc 13)
    'book' => [
        'trim_size_mm' => [200, 250], // [À CONFIRMER devis 0A]
        'booklet_min_words' => 3_000,
        'booklet_min_audio_minutes' => 25,
    ],

    // Offre et durées (R-2)
    'offer' => [
        'pilot_weeks' => 12,
        'core_months' => 12,
        'finalization_months' => 3,
        'dormant_after_months' => 15,
    ],

    // Histoires : fenêtre de restauration de la corbeille (R-4)
    'stories' => [
        'trash_retention_days' => 30,
    ],

    // Écoute famille (PRD §7, H2)
    'family' => [
        'listen_threshold_seconds' => 30,
        'comment_max_chars' => 280,
    ],

    // Créneaux d'envoi (décision T-28)
    'schedule' => [
        'gift_hour' => 9,
        'slots' => ['morning' => 9, 'afternoon' => 14, 'evening' => 18],
    ],

    // SMS : pays acceptant un expéditeur alphanumérique (bloc 05)
    'sms' => [
        'alphanumeric_countries' => ['FR', 'BE', 'CH', 'LU'],
    ],

    // Pilote et option téléphone (R-12 D-9)
    /*
     * Repli des réglages du pilote : la base (`PilotSettings`) devient la
     * source de vérité dès le premier enregistrement dans l'administration.
     * Les prix sont en **centimes entiers** — un prix en flottant finit par
     * produire 48,99 € au lieu de 49 €, et on ne s'en aperçoit qu'à la
     * première facture.
     */
    'pilot' => [
        'phone_option_cap' => 10,
        'phone_option_price_cents' => 2_500,
        'pilot_price_cents' => 8_900,
        'prevente_prices_cents' => [9_900, 12_900],
        // `[À CONFIRMER devis 0A]` : tant que l'imprimeur est inconnu, ce
        // prix est un placeholder, et aucune promesse de délai ne l'accompagne.
        'extra_copy_price_cents' => 4_500,
        // Le livre numérique : prix, et prix barré affiché à côté (T-137).
        'ebook_price_cents' => 2_500,
        'ebook_regular_price_cents' => 4_500,
        'gift_send_hour' => 9,
        'target_stories' => [10, 15],
        // La réduction de bienvenue de la page d'accueil (T-141), en pour
        // cent de la commande. Le coupon Stripe `STRIPE_COUPON_WELCOME` porte
        // le même pourcentage : c'est lui qui s'applique au paiement.
        'welcome_offer_discount_percent' => 10,
    ],

    // Binaires média (bloc 04 pour la concaténation, bloc 06 pour la durée)
    'media' => [
        'ffmpeg' => env('FFMPEG_BINARIES', '/usr/bin/ffmpeg'),
        'ffprobe' => env('FFPROBE_BINARIES', '/usr/bin/ffprobe'),
    ],

    // Sécurité : origines autorisées par la politique de contenu à servir
    // des médias. R2 en production, MinIO en local ; complété au bloc 04.
    'security' => [
        /*
         * Bornes des routes à jeton. Celle par **jeton** protège du
         * balayage : c'est elle qui compte, et elle ne bouge pas. Celle par
         * **IP** protège l'infrastructure, mais elle punit le partage de
         * connexion — une maison de retraite, une famille derrière un seul
         * routeur, une suite de tests bout en bout. Elle est donc réglable,
         * et large hors production (décision T-79).
         */
        'rate_limits' => [
            'tokens_per_token' => (int) env('THROTTLE_TOKENS_PER_TOKEN', 20),
            'tokens_per_ip' => (int) env('THROTTLE_TOKENS_PER_IP', 60),
        ],

        // L'origine que le **navigateur** contacte, et non celle que contacte
        // le serveur : c'est vers l'endpoint public que partent les envois
        // présignés, et une politique qui l'ignore les bloque en silence.
        'media_hosts' => array_values(array_unique(array_filter([
            env('R2_PUBLIC_ENDPOINT', env('R2_ENDPOINT')),
            env('R2_ENDPOINT'),
            env('AWS_ENDPOINT'),
        ]))),
    ],

    // Comptes semés en local et en test (jamais en production)
    'seeding' => [
        'admin_email' => env('ADMIN_EMAIL', 'admin@example.test'),
        'admin_password' => env('ADMIN_PASSWORD', 'password'),
    ],

    /*
     * L'extrait écoutable de la page d'accueil (T-149).
     *
     * Le chemin est relatif à `public/`, et l'absence du fichier n'est pas une
     * erreur : la carte du héros retombe alors sur sa frise décorative. C'est
     * ce qui permet de déployer la page avant l'audio, et de retirer l'audio
     * sans toucher au gabarit.
     *
     * La voix de l'extrait est générée. `hero_sample_disclosed` décide si la
     * carte le dit : la mention a été retirée le 5 septembre 2026 sur décision
     * du fondateur. La repasser à `true` la fait réapparaître, et rien d'autre
     * ne bouge.
     */
    'landing' => [
        'hero_sample' => 'audio/landing/hero.mp3',
        'hero_sample_disclosed' => false,
    ],

    // Photos (bloc 12)
    'photos' => [
        'max_bytes' => 20 * 1024 * 1024,
        'print_ready_min_shortest_side' => 1_200,
        'caption_max_chars' => 200,
    ],
];
