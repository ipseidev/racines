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
    'failed' => 'Queste credenziali non corrispondono ai nostri dati.',
    'password' => 'La password non è corretta',
    'throttle' => 'Troppi tentativi di accesso. Riprova tra :seconds secondi.',

    'pages' => [
        'login' => [
            'title' => 'Accedi',
            'description' => 'Ritrova il progetto, le domande e i familiari.',
        ],
        'register' => [
            'title' => 'Crea un account',
            'description' => 'Un account per seguire il progetto e ritrovare il tuo ordine.',
        ],
        'forgot_password' => [
            'title' => 'Password dimenticata',
            'description' => 'Inserisci la tua e-mail: ti inviamo un link per sceglierne una nuova.',
        ],
        'reset_password' => [
            'title' => 'Nuova password',
            'description' => 'Scegli una password che ricorderai.',
        ],
        'verify_email' => [
            'title' => 'Controlla la tua e-mail',
            'description' => 'Ti abbiamo appena inviato un link. Cliccalo per confermare il tuo indirizzo, poi torna qui.',
        ],
        'two_factor_challenge' => [
            'title' => 'Verifica in due passaggi',
            'description' => 'Un ultimo passaggio per proteggere il tuo account.',
        ],
        'confirm_password' => [
            'title' => 'Conferma la tua password',
            'description' => 'Questa parte è delicata. Conferma la password prima di continuare.',
        ],
    ],

    'fields' => [
        'name' => 'Il tuo nome',
        'email' => 'La tua e-mail',
        'password' => 'Password',
        'new_password' => 'Nuova password',
        'password_confirmation' => 'Conferma la password',
        'remember' => 'Ricordami',
        'code' => 'Codice di verifica',
        'recovery_code' => 'Codice di recupero',
        'show' => 'Mostra la password',
        'hide' => 'Nascondi la password',
    ],

    'actions' => [
        'login' => 'Accedi',
        'register' => 'Crea il mio account',
        'send_link' => 'Invia il link',
        'reset' => 'Salva la password',
        'resend' => 'Invia di nuovo l’e-mail',
        'logout' => 'Esci',
        'confirm' => 'Conferma',
        'continue' => 'Continua',
        'passkey' => 'Accedi con una passkey',
        'passkey_confirm' => 'Conferma con una passkey',
        'passkey_waiting' => 'Verifica…',
        'or_email' => 'oppure con l’e-mail',
        'or_password' => 'oppure con la password',
        'waiting' => 'Un attimo…',
    ],

    'links' => [
        'forgot' => 'Password dimenticata?',
        'no_account' => 'Non hai ancora un account?',
        'register' => 'Crea un account',
        'have_account' => 'Hai già un account?',
        'login' => 'Accedi',
        'back_to_login' => 'Torna all’accesso',
        'use_recovery' => 'Usa un codice di recupero',
        'use_code' => 'Usa il codice dell’app',
    ],

    'two_factor' => [
        'code' => 'Inserisci il codice mostrato dalla tua app di autenticazione.',
        'recovery' => 'Inserisci uno dei codici di recupero che hai conservato.',
        'or' => 'Oppure:',
    ],

    'verify' => [
        'sent' => 'Un nuovo link è appena partito verso il tuo indirizzo.',
        'expired' => 'Questo link era scaduto. Un nuovo link è appena partito verso il tuo indirizzo.',
        'mismatch' => 'Questo link conferma un indirizzo diverso da quello dell’account aperto qui. Esci e accedi con l’indirizzo da confermare.',
    ],
];
