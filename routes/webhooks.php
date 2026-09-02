<?php

declare(strict_types=1);

use App\Http\Controllers\Webhooks\AsrWebhookController;
use App\Http\Controllers\Webhooks\ResendWebhookController;
use App\Http\Controllers\Webhooks\TwilioStatusController;
use App\Http\Middleware\VerifyResendSignature;
use App\Http\Middleware\VerifyTwilioSignature;
use Illuminate\Support\Facades\Route;

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

Route::post('/webhooks/twilio/status', TwilioStatusController::class)
    ->middleware(VerifyTwilioSignature::class)
    ->name('webhooks.twilio.status');

Route::post('/webhooks/resend', ResendWebhookController::class)
    ->middleware(VerifyResendSignature::class)
    ->name('webhooks.resend');

// Rappel de transcription. La signature vit dans l'URL et couvre
// l'identifiant d'enregistrement : sans elle, on pourrait injecter une fausse
// transcription dans l'histoire de quelqu'un.
Route::post('/webhooks/asr/{provider}/{recording}', AsrWebhookController::class)
    ->whereIn('provider', ['gladia', 'deepgram', 'fake'])
    ->name('webhooks.asr');
