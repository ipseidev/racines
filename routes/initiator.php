<?php

declare(strict_types=1);

use App\Http\Controllers\Books\QrRevocationController;
use App\Http\Controllers\Initiator\BookController;
use App\Http\Controllers\Initiator\CopyLinkController;
use App\Http\Controllers\Initiator\DataController;
use App\Http\Controllers\Initiator\FamilyController;
use App\Http\Controllers\Initiator\OrdersController;
use App\Http\Controllers\Initiator\ProjectSettingsController;
use App\Http\Controllers\Initiator\QuestionsController;
use App\Http\Controllers\Initiator\SpaceController;
use App\Http\Controllers\Initiator\SpaceEntryController;
use App\Http\Controllers\Photos\PhotoController;
use App\Http\Middleware\EnsureProjectIsVisible;
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
| **Les adresses portent le projet** depuis le 21 septembre 2026 :
| `/espace/projets/{projet}/questions` et non `/espace/questions`. Elles le
| devinaient jusque-là, par `InitiatorProject::for()`, qui rendait le plus
| récent projet du compte — si bien qu'un second achat **cachait le premier
| en silence**. Mesuré sur un compte de démonstration : trois projets, un
| seul affiché, et l'invisible était le seul actif. Rien dans le tunnel
| n'empêche d'offrir à ses deux parents.
|
| `/espace` reste la porte : il redirige vers le projet quand il n'y en a
| qu'un, ce qui n'ajoute aucun clic à la quasi-totalité des comptes. La
| garde est `EnsureProjectIsVisible`, qui s'appuie sur `ProjectPolicy::view()`
| — donc `isMember()`, donc le propriétaire et l'éditeur désigné du livre.
| Elle répond **404** et non 403 : un 403 confirmerait que le projet existe.
|
| Ce que ces adresses ne décident **pas** : si deux parents font deux projets
| ou un projet à deux narrateurs. Le dossier laisse ce choix à la Gate
| Phase 1 (doc 03, multi-narrateurs), et porter l'identifiant dans l'URL ne
| le tranche pas — il rend seulement honnête ce que le modèle sait déjà.
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
        // La porte : elle mène au projet, elle n'en affiche aucun.
        Route::get('/', SpaceEntryController::class)->name('home');

        /*
         * Les anciennes adresses, qui ont vécu jusqu'au 21 septembre 2026.
         *
         * Elles sont dans des signets, dans des courriels déjà partis, et
         * dans l'historique des navigateurs. Les laisser répondre 404 aurait
         * transformé un rangement d'URL en panne pour ceux qui avaient pris
         * l'habitude d'y aller directement. Elles mènent au projet courant,
         * comme la porte.
         *
         * Les seules concernées sont les pages qu'on visite : une écriture
         * (`POST /espace/questions/ordre`) ne se redirige pas, elle n'a
         * jamais été tapée à la main.
         */
        foreach (['questions', 'proches', 'livre', 'donnees', 'reglages', 'ecoute'] as $ancienne) {
            Route::get('/'.$ancienne, [SpaceEntryController::class, 'legacy'])
                ->defaults('page', $ancienne)
                ->name('legacy.'.$ancienne);
        }

        Route::prefix('projets/{project}')
            ->middleware(EnsureProjectIsVisible::class)
            ->group(function (): void {
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
                // Retirer une question qu'on a écrite soi-même : les
                // questions du corpus s'écartent, les siennes se suppriment —
                // elles n'existent que pour ce projet.
                Route::delete('/questions/proposees/{story}', [QuestionsController::class, 'destroyPending'])->name('questions.pending_destroy');

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

                Route::post('/livre/code', [BookController::class, 'setCode'])->name('book.code');
                Route::delete('/livre/code', [BookController::class, 'removeCode'])->name('book.code.remove');

                Route::post('/livre/exemplaires', [BookController::class, 'extraCopies'])->name('book.extra_copies');

                Route::delete('/livre/histoires/{story}/qr', [QrRevocationController::class, 'initiatorDestroy'])->name('book.qr.revoke');
                Route::post('/livre/histoires/{story}/qr', [QrRevocationController::class, 'initiatorRestore'])->name('book.qr.restore');

                /*
                 * « Mes données » (bloc 14).
                 *
                 * La page qui rend la non-captivité visible : un export possible mais
                 * caché dans un courriel de support ne vaut rien.
                 */
                Route::get('/donnees', [DataController::class, 'index'])->name('data');
                Route::post('/donnees/export', [DataController::class, 'export'])->name('data.export');
                Route::post('/donnees/effacement', [DataController::class, 'erase'])->name('data.erase');

                Route::get('/reglages', [ProjectSettingsController::class, 'index'])->name('settings');
                Route::post('/reglages', [ProjectSettingsController::class, 'update'])->name('settings.update');
                Route::post('/reglages/langue', [ProjectSettingsController::class, 'setLocale'])->name('settings.locale');
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
});
