<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Routes narrateur
|--------------------------------------------------------------------------
|
| Toutes servies sur le domaine court des liens (doc 04 §9) et accessibles par
| jeton porteur uniquement : aucun compte, aucun mot de passe (doc 04 §12).
|
|   /r/{token}   enregistrement d'une histoire      (jeton record)
|   /n/{token}   espace personnel après code OTP    (jeton narrator_space)
|   /i/{token}   opt-in à l'invitation              (jeton invitation)
|
| Chaque groupe porte : resolve.token, throttle:tokens, no-store.
| Les routes arrivent aux blocs 03, 04, 07 et 10.
|
*/
