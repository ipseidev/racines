<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Messages envoyés par SMS et par courriel
|--------------------------------------------------------------------------
|
| Trois règles d'anti-hameçonnage (doc 04 §9) : la marque est nommée, la durée
| de validité est annoncée, et le message dit de ne communiquer le code à
| personne. Un code n'arrive jamais avec un lien à cliquer.
|
*/

return [

    'otp' => [
        'subject' => 'Votre code : :code',
        'greeting' => 'Bonjour,',
        'code_line' => 'Votre code est :code.',
        'expiry_line' => 'Il expire dans :minutes minutes.',
        'warning_line' => 'Ne le communiquez à personne, même à quelqu’un qui dirait appeler de notre part.',
        'sms' => ':brand : votre code est :code. Il expire dans :minutes minutes. Ne le communiquez à personne.',
    ],

];
