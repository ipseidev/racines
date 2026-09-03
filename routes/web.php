<?php

declare(strict_types=1);

use App\Http\Controllers\Checkout\CheckoutController;
use App\Http\Controllers\Public\LandingController;
use App\Http\Controllers\Public\LegalController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routes publiques et espace de l'Initiateur·rice
|--------------------------------------------------------------------------
|
| Sur le domaine principal, contrairement aux pages à jeton qui vivent sur le
| domaine court des liens. Aucune de ces pages ne demande de compte, sauf
| l'espace et l'étape de paiement.
|
*/

Route::get('/', LandingController::class)->name('home');

Route::get('/essai', [LandingController::class, 'demo'])->name('demo');

// Pages légales, rendues depuis des fichiers markdown : elles sont relues par
// un conseil, et un conseil relit un texte, pas un composant React.
Route::get('/cgv', [LegalController::class, 'terms'])->name('legal.terms');
Route::get('/confidentialite', [LegalController::class, 'privacy'])->name('legal.privacy');
Route::get('/mentions-legales', [LegalController::class, 'imprint'])->name('legal.imprint');
Route::get('/consentements', [LegalController::class, 'consents'])->name('legal.consents');

/*
 * Le tunnel d'achat. Les cinq premières étapes sont ouvertes : le compte se
 * crée à la quatrième, et exiger une connexion avant reviendrait à demander
 * un mot de passe à quelqu'un qui ne sait pas encore ce qu'il achète.
 */
Route::get('/acheter', [CheckoutController::class, 'show'])->name('checkout.show');

Route::post('/acheter/etape/{step}', [CheckoutController::class, 'store'])
    ->whereNumber('step')
    ->name('checkout.step');

Route::post('/acheter/payer', [CheckoutController::class, 'pay'])
    ->middleware('auth')
    ->name('checkout.pay');

Route::get('/acheter/merci', [CheckoutController::class, 'thanks'])->name('checkout.thanks');

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/initiator.php';
require __DIR__.'/settings.php';
