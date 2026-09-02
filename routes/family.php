<?php

declare(strict_types=1);

use App\Http\Controllers\Family\HomePageController;
use App\Http\Controllers\Family\ListenProgressController;
use App\Http\Controllers\Family\ReactionController;
use App\Http\Controllers\Family\StoryPageController;
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
| Les routes /q, /a et /x arrivent aux blocs 09, 13 et 14.
|
*/

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

    Route::post('/l/{token}/stories/{story}/reactions', [ReactionController::class, 'store'])
        ->name('family.stories.react');
});
