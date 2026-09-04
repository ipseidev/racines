<?php

declare(strict_types=1);

/*
 * Les pages de compte : connexion, inscription, mot de passe, vérification,
 * seconde étape. Servies sur les routes de Fortify (Translations::SPACES).
 *
 * Le kit les livrait en anglais et en dur. Elles parlent la langue du reste,
 * et s'adressent à la personne qui organise : « vous », des phrases courtes,
 * pas de jargon de sécurité là où un mot suffit.
 */
return [
    'failed' => 'Ces identifiants ne correspondent pas à nos enregistrements.',
    'password' => 'Le mot de passe est incorrect',
    'throttle' => 'Tentatives de connexion trop nombreuses. Veuillez essayer de nouveau dans :seconds secondes.',

    'pages' => [
        'login' => [
            'title' => 'Se connecter',
            'description' => 'Retrouvez le projet, les questions et les proches.',
        ],
        'register' => [
            'title' => 'Créer un compte',
            'description' => 'Un compte pour suivre le projet et retrouver votre commande.',
        ],
        'forgot_password' => [
            'title' => 'Mot de passe oublié',
            'description' => 'Donnez votre courriel : nous vous envoyons un lien pour en choisir un nouveau.',
        ],
        'reset_password' => [
            'title' => 'Nouveau mot de passe',
            'description' => 'Choisissez un mot de passe que vous retiendrez.',
        ],
        'verify_email' => [
            'title' => 'Vérifiez votre courriel',
            'description' => 'Nous venons de vous envoyer un lien. Cliquez dessus pour confirmer votre adresse, puis revenez ici.',
        ],
        'two_factor_challenge' => [
            'title' => 'Vérification en deux étapes',
            'description' => 'Une dernière étape pour protéger votre compte.',
        ],
        'confirm_password' => [
            'title' => 'Confirmez votre mot de passe',
            'description' => 'Cette partie est sensible. Confirmez votre mot de passe avant de continuer.',
        ],
    ],

    'fields' => [
        'name' => 'Votre nom',
        'email' => 'Votre courriel',
        'password' => 'Mot de passe',
        'new_password' => 'Nouveau mot de passe',
        'password_confirmation' => 'Confirmez le mot de passe',
        'remember' => 'Rester connecté·e',
        'code' => 'Code de vérification',
        'recovery_code' => 'Code de secours',
        'show' => 'Afficher le mot de passe',
        'hide' => 'Masquer le mot de passe',
    ],

    'actions' => [
        'login' => 'Me connecter',
        'register' => 'Créer mon compte',
        'send_link' => 'Envoyer le lien',
        'reset' => 'Enregistrer le mot de passe',
        'resend' => 'Renvoyer le courriel',
        'logout' => 'Se déconnecter',
        'confirm' => 'Confirmer',
        'continue' => 'Continuer',
        'passkey' => 'Me connecter avec une clé d’accès',
        'passkey_confirm' => 'Confirmer avec une clé d’accès',
        'passkey_waiting' => 'Vérification…',
        'or_email' => 'ou avec un courriel',
        'or_password' => 'ou avec le mot de passe',
        'waiting' => 'Un instant…',
    ],

    'links' => [
        'forgot' => 'Mot de passe oublié ?',
        'no_account' => 'Pas encore de compte ?',
        'register' => 'Créer un compte',
        'have_account' => 'Déjà un compte ?',
        'login' => 'Me connecter',
        'back_to_login' => 'Revenir à la connexion',
        'use_recovery' => 'Utiliser un code de secours',
        'use_code' => 'Utiliser le code de l’application',
    ],

    'two_factor' => [
        'code' => 'Entrez le code affiché par votre application d’authentification.',
        'recovery' => 'Entrez l’un des codes de secours que vous avez conservés.',
        'or' => 'Ou bien :',
    ],

    'verify' => [
        'sent' => 'Un nouveau lien vient de partir vers votre adresse.',
    ],
];
