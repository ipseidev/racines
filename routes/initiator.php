<?php

declare(strict_types=1);

use App\Http\Controllers\Books\QrRevocationController;
use App\Http\Controllers\Initiator\BookController;
use App\Http\Controllers\Initiator\CopyLinkController;
use App\Http\Controllers\Initiator\FamilyController;
use App\Http\Controllers\Initiator\OrdersController;
use App\Http\Controllers\Initiator\ProjectSettingsController;
use App\Http\Controllers\Initiator\QuestionsController;
use App\Http\Controllers\Initiator\SpaceController;
use App\Http\Controllers\Photos\PhotoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Espace de l'Initiateur·rice
|--------------------------------------------------------------------------
|
| La personne qui a acheté le service : elle suit, organise et invite, sans
| devenir chef de projet. Elle ne voit **jamais** le texte ni l'audio d'une
| histoire que le narrateur n'a pas partagée — c'est le même invariant que
| pour les proches, et il vaut aussi pour celle qui paie.
|
| Tout est sous `auth` et `verified`, sauf les commandes : quelqu'un doit
| pouvoir exercer sa rétractation sans avoir cliqué le lien de vérification
| de son courriel.
|
*/

Route::middleware('auth')->prefix('espace')->name('initiator.')->group(function (): void {
    Route::get('/commandes', [OrdersController::class, 'index'])->name('orders');
    Route::post('/commandes/{order}/retractation', [OrdersController::class, 'withdraw'])->name('orders.withdraw');
    // Compléter une commande déjà passée (T-184) : la seule autre porte vers
    // l'option téléphone était l'alerte du moteur, après trois semaines de
    // silence. Un acheteur qui réalise son oubli le lendemain n'avait rien.
    Route::post('/commandes/{order}/completer', [OrdersController::class, 'topUp'])->name('orders.top_up');

    Route::middleware('verified')->group(function (): void {
        Route::get('/', SpaceController::class)->name('dashboard');

        // Réémission : un lien en clair n'existe qu'entre son émission et
        // son envoi, il ne se relit pas en base (invariant du bloc 03).
        Route::post('/lien/question', [CopyLinkController::class, 'record'])->name('link.record');

        // « Écouter comme un proche » ouvre la page d'écoute directement, dans
        // un nouvel onglet : un lien à copier pour soi-même n'avait pas de
        // sens, et le bouton passait pour cassé (T-149). Un GET qui réémet un
        // jeton, mais le sien, et le précédent n'avait pas d'autre lecteur.
        Route::get('/ecoute', [CopyLinkController::class, 'listen'])->name('listen');

        Route::get('/questions', [QuestionsController::class, 'index'])->name('questions');
        Route::post('/questions/ordre', [QuestionsController::class, 'reorder'])->name('questions.reorder');
        Route::post('/questions/{question}/exclure', [QuestionsController::class, 'exclude'])->name('questions.exclude');
        Route::post('/questions/personnalisee', [QuestionsController::class, 'store'])->name('questions.store');

        Route::get('/proches', [FamilyController::class, 'index'])->name('family');
        Route::post('/proches', [FamilyController::class, 'store'])->name('family.store');
        Route::post('/proches/{member}/renvoyer', [FamilyController::class, 'reissue'])->name('family.reissue');
        Route::delete('/proches/{member}', [FamilyController::class, 'destroy'])->name('family.destroy');

        /*
         * Le livre (bloc 13).
         *
         * L'éditeur désigné a les mêmes droits que l'Initiateur·rice ici :
         * c'est souvent lui qui relit les noms propres, et le lui refuser
         * obligerait à se passer un mot de passe.
         */
        Route::get('/livre', [BookController::class, 'index'])->name('book');
        Route::post('/livre', [BookController::class, 'update'])->name('book.update');
        Route::post('/livre/bat', [BookController::class, 'render'])->name('book.render');
        Route::post('/livre/accord', [BookController::class, 'approve'])->name('book.approve');

        Route::delete('/livre/histoires/{story}/qr', [QrRevocationController::class, 'initiatorDestroy'])->name('book.qr.revoke');
        Route::post('/livre/histoires/{story}/qr', [QrRevocationController::class, 'initiatorRestore'])->name('book.qr.restore');

        Route::get('/reglages', [ProjectSettingsController::class, 'index'])->name('settings');
        Route::post('/reglages', [ProjectSettingsController::class, 'update'])->name('settings.update');
        Route::post('/reglages/lexique', [ProjectSettingsController::class, 'addLexicon'])->name('settings.lexicon');
        Route::delete('/reglages/lexique/{entry}', [ProjectSettingsController::class, 'removeLexicon'])->name('settings.lexicon.remove');
        Route::post('/reglages/pause', [ProjectSettingsController::class, 'pause'])->name('settings.pause');

        /*
         * Les photos, côté Initiateur·rice (bloc 12). C'est souvent elle qui
         * a les photos de famille numérisées, et le narrateur qui n'a pas
         * envie de les chercher.
         */
        Route::post('/histoires/{story}/photos', [PhotoController::class, 'store'])->name('photos.store');
        Route::patch('/histoires/{story}/photos/{photo}', [PhotoController::class, 'updateCaption'])->name('photos.caption');
        Route::delete('/histoires/{story}/photos/{photo}', [PhotoController::class, 'destroy'])->name('photos.destroy');
    });
});
