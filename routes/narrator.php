<?php

declare(strict_types=1);

use App\Http\Controllers\Links\VcardController;
use App\Http\Controllers\Narrator\ClientEventController;
use App\Http\Controllers\Narrator\OtpChallengeController;
use App\Http\Controllers\Narrator\RecordingUploadController;
use App\Http\Controllers\Narrator\RecordPageController;
use App\Http\Controllers\Narrator\RequestNewLinkController;
use App\Http\Controllers\Narrator\WrittenAnswerController;
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

// Fiche contact : sans jeton, mais sur le domaine des liens, pour qu'un
// narrateur puisse enregistrer l'expéditeur de ses questions (doc 04 §9).
Route::get('/vcard', VcardController::class)
    ->middleware('throttle:tokens')
    ->name('narrator.vcard');

Route::middleware(['throttle:tokens', 'no-store'])->group(function (): void {
    // Page d'enregistrement : explication, permission, enregistrement,
    // vérification, envoi, confirmation (bloc 04).
    Route::get('/r/{token}', RecordPageController::class)
        ->middleware('resolve.token:record')
        ->name('narrator.record.show');

    // Demander un nouveau lien depuis la page d'erreur. Seule route qui ne
    // résout pas son jeton : elle agit justement parce qu'il est mort.
    Route::post('/r/{token}/request-new-link', RequestNewLinkController::class)
        ->middleware('throttle:new-link')
        ->name('narrator.record.request_new_link');

    // Envoi de l'audio : un segment par continuité de flux, des parts de
    // 5 Mio déposées directement sur le stockage par URL présignée.
    Route::middleware('resolve.token:record')->group(function (): void {
        Route::post('/r/{token}/recordings', [RecordingUploadController::class, 'initiate'])
            ->name('narrator.recordings.initiate');

        Route::post('/r/{token}/recordings/{recording}/segments', [RecordingUploadController::class, 'openSegment'])
            ->name('narrator.recordings.open_segment');

        Route::post('/r/{token}/recordings/{recording}/segments/{segment}/parts/{part}/sign', [RecordingUploadController::class, 'sign'])
            ->whereNumber(['segment', 'part'])
            ->name('narrator.recordings.sign');

        Route::post('/r/{token}/recordings/{recording}/complete', [RecordingUploadController::class, 'complete'])
            ->name('narrator.recordings.complete');

        Route::post('/r/{token}/recordings/{recording}/abort', [RecordingUploadController::class, 'abort'])
            ->name('narrator.recordings.abort');

        // Ce que le navigateur rapporte de la séance : c'est la matière du
        // taux d'échec de capture avant confirmation (doc 04 §11).
        //
        // Seule route à ne pas porter `throttle:tokens` : ses 20 requêtes par
        // minute et par jeton, faites pour les pages, étoufferaient la mesure.
        // `client-events` reprend une borne par IP et en ajoute une par lien.
        Route::post('/r/{token}/events', [ClientEventController::class, 'store'])
            ->withoutMiddleware('throttle:tokens')
            ->middleware('throttle:client-events')
            ->name('narrator.events.store');

        // Repli écrit (P0-5) : pas un lot de consolation, la même machine
        // d'états qu'une réponse orale.
        Route::post('/r/{token}/written-answer', [WrittenAnswerController::class, 'store'])
            ->name('narrator.written_answer.store');
    });

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
