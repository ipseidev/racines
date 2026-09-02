<?php

declare(strict_types=1);

use App\Http\Controllers\Narrator\OtpChallengeController;
use App\Http\Controllers\Narrator\RequestNewLinkController;
use App\Http\Controllers\Narrator\TokenProbeController;
use Illuminate\Support\Facades\Route;

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
| Chaque groupe porte : resolve.token, throttle:tokens, no-store. Un test
| vérifie qu'aucune route de ce fichier n'y échappe.
| Les routes /n et /i arrivent aux blocs 07 et 10.
|
*/

Route::middleware(['throttle:tokens', 'no-store'])->group(function (): void {
    // Page de vérification du bloc 03, remplacée par la vraie page
    // d'enregistrement au bloc 04.
    Route::get('/r/{token}', TokenProbeController::class)
        ->middleware('resolve.token:record')
        ->name('narrator.record.probe');

    // Demander un nouveau lien depuis la page d'erreur. Seule route qui ne
    // résout pas son jeton : elle agit justement parce qu'il est mort.
    Route::post('/r/{token}/request-new-link', RequestNewLinkController::class)
        ->middleware('throttle:new-link')
        ->name('narrator.record.request_new_link');

    // Code à usage unique pour les actes sensibles (doc 04 §12).
    Route::middleware('resolve.token:record')->group(function (): void {
        Route::get('/r/{token}/code', [OtpChallengeController::class, 'show'])
            ->name('narrator.otp.show');

        Route::post('/r/{token}/code', [OtpChallengeController::class, 'send'])
            ->middleware('throttle:otp-request')
            ->name('narrator.otp.send');

        Route::post('/r/{token}/code/verify', [OtpChallengeController::class, 'verify'])
            ->middleware('throttle:otp-verify')
            ->name('narrator.otp.verify');
    });
});
