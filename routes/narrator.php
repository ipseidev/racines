<?php

declare(strict_types=1);

use App\Http\Controllers\Links\VcardController;
use App\Http\Controllers\Narrator\ClientEventController;
use App\Http\Controllers\Narrator\HideOwnStoryController;
use App\Http\Controllers\Narrator\OptInController;
use App\Http\Controllers\Narrator\OtpChallengeController;
use App\Http\Controllers\Narrator\PauseController;
use App\Http\Controllers\Narrator\RecordingUploadController;
use App\Http\Controllers\Narrator\RecordPageController;
use App\Http\Controllers\Narrator\RequestNewLinkController;
use App\Http\Controllers\Narrator\ReviewController;
use App\Http\Controllers\Narrator\ShareDecisionController;
use App\Http\Controllers\Narrator\SpaceAccessController;
use App\Http\Controllers\Narrator\SpaceController;
use App\Http\Controllers\Narrator\SpaceOtpController;
use App\Http\Controllers\Narrator\ThanksController;
use App\Http\Controllers\Narrator\WithdrawalController;
use App\Http\Controllers\Narrator\WrittenAnswerController;
use App\Http\Controllers\Photos\PhotoController;
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

// Remerciement après un geste qui a révoqué le lien : sans jeton, donc sans
// rien de personnel à montrer (bloc 07 §6.4).
Route::get('/merci', ThanksController::class)
    ->middleware(['throttle:tokens', 'no-store'])
    ->name('narrator.thanks');

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

        // Les trois choix de fin d'enregistrement (variante A, bloc 07).
        // Aucun acte sensible : le narrateur décide du sort de l'histoire
        // que ce lien même vient de porter.
        Route::post('/r/{token}/share-decision', [ShareDecisionController::class, 'store'])
            ->name('narrator.share_decision.store');

        // Masquer l'histoire que ce lien porte : sans code, et c'est le seul
        // retrait dans ce cas. Quelqu'un qui regrette ce qu'il vient de
        // raconter doit pouvoir le retirer tout de suite (bloc 07 §6.5).
        Route::post('/r/{token}/hide', HideOwnStoryController::class)
            ->name('narrator.record.hide');

        // Relecture (variante B et « décider plus tard »), sur le même jeton :
        // le narrateur n'a pas deux liens à distinguer dans ses SMS.
        Route::get('/r/{token}/review', [ReviewController::class, 'show'])
            ->name('narrator.review.show');

        Route::post('/r/{token}/review/edit', [ReviewController::class, 'edit'])
            ->name('narrator.review.edit');

        Route::post('/r/{token}/review/decision', [ReviewController::class, 'decide'])
            ->name('narrator.review.decision');

        /*
         * Les photos (bloc 12), sur le même jeton et sans identifiant
         * d'histoire : le lien porte **une** histoire, et son périmètre
         * s'arrête là. Rien à deviner, rien à confondre.
         */
        Route::post('/r/{token}/photos', [PhotoController::class, 'store'])
            ->name('narrator.photos.store');

        Route::patch('/r/{token}/photos/{photo}', [PhotoController::class, 'updateCaption'])
            ->name('narrator.photos.caption');

        Route::delete('/r/{token}/photos/{photo}', [PhotoController::class, 'destroy'])
            ->name('narrator.photos.destroy');
    });

    /*
     * Espace narrateur (bloc 07). Le jeton porte une **personne**, non une
     * histoire : c'est pourquoi chaque action vérifie en plus que l'histoire
     * visée est bien la sienne. Sans ça, changer un identifiant dans l'URL
     * suffirait à agir sur le récit de quelqu'un d'autre.
     */
    Route::get('/n/request', [SpaceAccessController::class, 'show'])
        ->name('narrator.space.request.show');

    Route::post('/n/request', [SpaceAccessController::class, 'request'])
        ->middleware('throttle:space-access')
        ->name('narrator.space.request.send');

    Route::post('/n/verify', [SpaceAccessController::class, 'verify'])
        ->middleware('throttle:space-verify')
        ->name('narrator.space.request.verify');

    Route::middleware('resolve.token:narrator_space')->group(function (): void {
        Route::get('/n/{token}', SpaceController::class)
            ->name('narrator.space.show');

        Route::get('/n/{token}/code', [SpaceOtpController::class, 'show'])
            ->name('narrator.space.otp.show');

        Route::post('/n/{token}/code', [SpaceOtpController::class, 'send'])
            ->middleware('throttle:otp-request')
            ->name('narrator.space.otp.send');

        Route::post('/n/{token}/code/verify', [SpaceOtpController::class, 'verify'])
            ->middleware('throttle:otp-verify')
            ->name('narrator.space.otp.verify');

        // Tous les retraits sont des actes sensibles depuis l'espace : le
        // jeton y donne accès à toutes les histoires de la personne, et il a
        // pu être ouvert il y a un moment.
        Route::middleware('sensitive:narrator.space.otp.show')->group(function (): void {
            Route::post('/n/{token}/stories/{story}/hide', [WithdrawalController::class, 'hide'])
                ->name('narrator.space.stories.hide');

            Route::post('/n/{token}/stories/{story}/unhide', [WithdrawalController::class, 'unhide'])
                ->name('narrator.space.stories.unhide');

            Route::post('/n/{token}/stories/{story}/trash', [WithdrawalController::class, 'trash'])
                ->name('narrator.space.stories.trash');

            Route::post('/n/{token}/stories/{story}/restore', [WithdrawalController::class, 'restore'])
                ->name('narrator.space.stories.restore');

            Route::post('/n/{token}/stories/{story}/delete', [WithdrawalController::class, 'destroy'])
                ->name('narrator.space.stories.delete');

            Route::post('/n/{token}/stories/{story}/visibility', [WithdrawalController::class, 'visibility'])
                ->name('narrator.space.stories.visibility');

            Route::post('/n/{token}/pause', PauseController::class)
                ->name('narrator.space.pause');
        });

        /*
         * Les photos depuis l'espace : **pas** un acte sensible.
         *
         * Retirer une histoire est irréversible pour la famille ; joindre une
         * photo ne l'est pas, et exiger un code à chaque photo découragerait
         * précisément la personne qu'on veut encourager. Le retrait d'une
         * photo n'en est pas non plus : elle vient d'être déposée, et on ne
         * demande pas un code pour défaire ce qu'on vient de faire.
         */
        Route::post('/n/{token}/stories/{story}/photos', [PhotoController::class, 'store'])
            ->name('narrator.space.photos.store');

        Route::patch('/n/{token}/stories/{story}/photos/{photo}', [PhotoController::class, 'updateCaption'])
            ->name('narrator.space.photos.caption');

        Route::delete('/n/{token}/stories/{story}/photos/{photo}', [PhotoController::class, 'destroy'])
            ->name('narrator.space.photos.destroy');
    });

    /*
     * Opt-in du narrateur (bloc 10). Le moment H0 : le cadeau se propose, il
     * ne s'impose pas. Cette page ne propose **aucun** enregistrement avant
     * l'acceptation, et les deux boutons sont de même taille.
     */
    Route::get('/i/farewell', [OptInController::class, 'farewell'])
        ->name('narrator.optin.farewell');

    Route::middleware('resolve.token:invitation')->group(function (): void {
        Route::get('/i/{token}', [OptInController::class, 'show'])
            ->name('narrator.optin.show');

        Route::post('/i/{token}/accepter', [OptInController::class, 'accept'])
            ->name('narrator.optin.accept');

        Route::post('/i/{token}/refuser', [OptInController::class, 'refuse'])
            ->name('narrator.optin.refuse');

        Route::get('/i/{token}/bienvenue', [OptInController::class, 'welcome'])
            ->name('narrator.optin.welcome');

        Route::post('/i/{token}/souhaits', [OptInController::class, 'storeDirectives'])
            ->name('narrator.optin.directives');
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
