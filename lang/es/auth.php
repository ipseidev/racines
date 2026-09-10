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
    'failed' => 'Estas credenciales no coinciden con nuestros registros.',
    'password' => 'La contraseña no es correcta',
    'throttle' => 'Demasiados intentos de inicio de sesión. Vuelve a intentarlo en :seconds segundos.',

    'pages' => [
        'login' => [
            'title' => 'Iniciar sesión',
            'description' => 'Vuelve a tu proyecto, a las preguntas y a tus familiares.',
        ],
        'register' => [
            'title' => 'Crear una cuenta',
            'description' => 'Una cuenta para seguir el proyecto y consultar tu pedido.',
        ],
        'forgot_password' => [
            'title' => 'Recuperar la contraseña',
            'description' => 'Escribe tu correo electrónico: te enviaremos un enlace para elegir una contraseña nueva.',
        ],
        'reset_password' => [
            'title' => 'Nueva contraseña',
            'description' => 'Elige una contraseña que puedas recordar.',
        ],
        'verify_email' => [
            'title' => 'Revisa tu correo electrónico',
            'description' => 'Acabamos de enviarte un enlace. Haz clic en él para confirmar tu dirección y luego vuelve aquí.',
        ],
        'two_factor_challenge' => [
            'title' => 'Verificación en dos pasos',
            'description' => 'Un último paso para proteger tu cuenta.',
        ],
        'confirm_password' => [
            'title' => 'Confirma tu contraseña',
            'description' => 'Esta parte es delicada. Confirma tu contraseña antes de continuar.',
        ],
    ],

    'fields' => [
        'name' => 'Tu nombre',
        'email' => 'Tu correo electrónico',
        'password' => 'Contraseña',
        'new_password' => 'Nueva contraseña',
        'password_confirmation' => 'Confirma la contraseña',
        'remember' => 'Mantener la sesión iniciada',
        'code' => 'Código de verificación',
        'recovery_code' => 'Código de recuperación',
        'show' => 'Mostrar la contraseña',
        'hide' => 'Ocultar la contraseña',
    ],

    'actions' => [
        'login' => 'Iniciar sesión',
        'register' => 'Crear mi cuenta',
        'send_link' => 'Enviar el enlace',
        'reset' => 'Guardar la contraseña',
        'resend' => 'Reenviar el correo electrónico',
        'logout' => 'Cerrar sesión',
        'confirm' => 'Confirmar',
        'continue' => 'Continuar',
        'passkey' => 'Iniciar sesión con una llave de acceso',
        'passkey_confirm' => 'Confirmar con una llave de acceso',
        'passkey_waiting' => 'Verificando…',
        'or_email' => 'o con un correo electrónico',
        'or_password' => 'o con la contraseña',
        'waiting' => 'Un momento…',
    ],

    'links' => [
        'forgot' => '¿Has olvidado tu contraseña?',
        'no_account' => '¿Aún no tienes cuenta?',
        'register' => 'Crear una cuenta',
        'have_account' => '¿Ya tienes cuenta?',
        'login' => 'Iniciar sesión',
        'back_to_login' => 'Volver al inicio de sesión',
        'use_recovery' => 'Usar un código de recuperación',
        'use_code' => 'Usar el código de la aplicación',
    ],

    'two_factor' => [
        'code' => 'Introduce el código que muestra tu aplicación de autenticación.',
        'recovery' => 'Introduce uno de los códigos de recuperación que has guardado.',
        'or' => 'O bien:',
    ],

    'verify' => [
        'sent' => 'Acabamos de enviar un nuevo enlace a tu dirección.',
        'expired' => 'Este enlace había caducado. Acabamos de enviar uno nuevo a tu dirección.',
        'mismatch' => 'Este enlace confirma una dirección distinta a la de la cuenta abierta aquí. Cierra sesión y vuelve a entrar con la dirección que quieres confirmar.',
    ],
];
