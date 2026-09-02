<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Webhooks entrants
|--------------------------------------------------------------------------
|
| Hors du groupe « web » : pas de session, pas de CSRF. Chaque route vérifie la
| signature du fournisseur avant de lire le corps de la requête, et un test
| couvre systématiquement le cas de la signature invalide.
|
|   /webhooks/twilio/status   livraison des SMS        (bloc 05)
|   /webhooks/resend          livraison des emails     (bloc 05)
|   /webhooks/asr/{provider}  transcription terminée   (bloc 06)
|   /stripe/webhook           paiements, via Cashier   (bloc 10)
|
*/
