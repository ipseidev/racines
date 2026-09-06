<?php

declare(strict_types=1);

use App\Http\Controllers\Family\HomePageController;
use App\Http\Controllers\Family\ListenProgressController;
use App\Http\Controllers\Family\ReactionController;
use App\Http\Controllers\Family\StoryPageController;
use App\Http\Controllers\Initiator\OneTapController;
use App\Http\Controllers\Photos\PhotoController;
use App\Http\Controllers\Qr\FamilyCodeController;
use App\Http\Controllers\Qr\QrListenController;
use App\Http\Controllers\Qr\QrPageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routes famille
|--------------------------------------------------------------------------
|
| Servies sur le domaine court des liens. Lecture seule, jetons distincts de
| ceux du narrateur (doc 04 §12).
|
|   /l/{token}   écoute d'un projet ou d'une histoire  (listen_project, listen_story)
|   /q/{token}   page atteinte par un QR imprimé       (jeton qr)
|   /a/{token}   action en un tap de l'Initiateur·rice (jeton action)
|   /x/{token}   téléchargement d'un export            (jeton export)
|
| Aucune histoire n'est servie ici sans passer par VisibleStoriesForFamilyMember.
| Les routes /q et /x arrivent aux blocs 13 et 14.
|
*/

/*
 * Actions en un tap de l'Initiateur·rice (bloc 09).
 *
 * `GET` lit le jeton **sans le consommer** : la page de confirmation d'un
 * lien à usage unique doit pouvoir s'afficher sans le griller. Seul le `POST`
 * le consomme, et une seule fois.
 */
Route::middleware(['throttle:tokens', 'no-store'])->group(function (): void {
    Route::get('/a/{token}', [OneTapController::class, 'show'])
        ->middleware('resolve.token:action,peek')
        ->name('initiator.one_tap.show');

    Route::post('/a/{token}', [OneTapController::class, 'store'])
        ->middleware('resolve.token:action')
        ->name('initiator.one_tap.store');
});

/*
 * Les QR imprimés dans le livre (bloc 13).
 *
 * Un groupe à part, et non une entrée du groupe famille : le jeton n'est pas
 * du même type, la page ne montre pas la même chose, et surtout **personne
 * n'est identifié** — un livre se prête. Les mélanger aurait tôt fait de
 * laisser passer une réaction ou une liste d'histoires sur une page publique.
 */
Route::middleware([
    'throttle:tokens',
    'no-store',
    'resolve.token:qr',
])->group(function (): void {
    Route::get('/q/{token}', QrPageController::class)->name('family.qr');

    Route::post('/q/{token}/code', FamilyCodeController::class)->name('family.qr.code');

    // Son propre limiteur, comme côté famille : les vingt requêtes par minute
    // qui protègent les pages étoufferaient la mesure d'écoute.
    Route::post('/q/{token}/listen', QrListenController::class)
        ->withoutMiddleware('throttle:tokens')
        ->middleware('throttle:client-events')
        ->name('family.qr.listen');
});

Route::middleware([
    'throttle:tokens',
    'no-store',
    'resolve.token:listen_project|listen_story',
])->group(function (): void {
    Route::get('/l/{token}', HomePageController::class)
        ->name('family.home');

    Route::get('/l/{token}/stories/{story}', StoryPageController::class)
        ->name('family.stories.show');

    // Ce que le lecteur audio rapporte : des secondes, toutes les dix
    // secondes et à la pause. Son propre limiteur, comme les événements du
    // navigateur côté narrateur — les vingt requêtes par minute qui
    // protègent les pages étoufferaient la mesure.
    Route::post('/l/{token}/stories/{story}/listen', [ListenProgressController::class, 'store'])
        ->withoutMiddleware('throttle:tokens')
        ->middleware('throttle:client-events')
        ->name('family.stories.listen');

    /*
     * Les photos d'un proche contributeur (bloc 12).
     *
     * Le droit de contribuer est explicite, accordé personne par personne par
     * l'Initiateur·rice, et vérifié par `PhotoAccess` : un jeton d'écoute
     * valide ne suffit pas. Retirer se limite à ses propres photos — un
     * cercle d'écoute n'a pas besoin d'un outil de plus pour se disputer.
     */
    Route::post('/l/{token}/stories/{story}/photos', [PhotoController::class, 'store'])
        ->name('family.photos.store');

    Route::patch('/l/{token}/stories/{story}/photos/{photo}', [PhotoController::class, 'updateCaption'])
        ->name('family.photos.caption');

    Route::delete('/l/{token}/stories/{story}/photos/{photo}', [PhotoController::class, 'destroy'])
        ->name('family.photos.destroy');

    Route::post('/l/{token}/stories/{story}/reactions', [ReactionController::class, 'store'])
        ->name('family.stories.react');
});
