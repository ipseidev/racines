<?php

declare(strict_types=1);

use App\Enums\Locale;
use App\Http\Controllers\Checkout\CheckoutController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\Public\LandingController;
use App\Http\Controllers\Public\LegalController;
use App\Http\Controllers\Public\ManifestController;
use App\Http\Controllers\Public\RobotsController;
use App\Http\Controllers\Public\SitemapController;
use App\Http\Controllers\Public\WelcomeOfferController;
use App\Support\LocalizedRoutes;
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

/*
 * Les pages publiques, une adresse par langue (T-238).
 *
 * Le corps ci-dessous est enregistré une fois par locale par
 * `LocalizedRoutes::register()` : en français à la racine sous ses noms
 * d'origine, puis sous `/it/`, `/es/`, `/fr-ch/`, `/it-ch/` avec un préfixe
 * de nom (`it.home`). Les segments traduits vivent dans
 * `lang/{langue}/routes.php`. Le témoin de la page de vente et les écritures
 * du tunnel ne sont pas déclinés : voir plus bas.
 */
LocalizedRoutes::register(function (Locale $locale): void {
    $uri = fn (string $name): string => LocalizedRoutes::uri($name, $locale);

    Route::get($uri('home'), LandingController::class)->name('home');

    Route::get($uri('demo'), [LandingController::class, 'demo'])->name('demo');
    Route::get($uri('how_it_works'), [LandingController::class, 'howItWorks'])->name('how_it_works');
    Route::get($uri('faq'), [LandingController::class, 'faq'])->name('faq');
    Route::get($uri('books'), [LandingController::class, 'books'])->name('books');

    // Pages légales, rendues depuis des fichiers markdown : elles sont relues
    // par un conseil, et un conseil relit un texte, pas un composant React.
    // Une traduction absente retombe sur le texte français, qui fait foi.
    Route::get($uri('legal.terms'), [LegalController::class, 'terms'])->name('legal.terms');
    Route::get($uri('legal.privacy'), [LegalController::class, 'privacy'])->name('legal.privacy');
    Route::get($uri('legal.imprint'), [LegalController::class, 'imprint'])->name('legal.imprint');
    Route::get($uri('legal.consents'), [LegalController::class, 'consents'])->name('legal.consents');

    // Les deux pages du tunnel ; ses écritures sont plus bas, sans préfixe.
    Route::get($uri('checkout.show'), [CheckoutController::class, 'show'])->name('checkout.show');
    Route::get($uri('checkout.thanks'), [CheckoutController::class, 'thanks'])->name('checkout.thanks');
});

// Le sélecteur de langue des pages sans adresse déclinée (espace, comptes,
// pages à jeton) : pose le témoin, met le compte à jour, revient en arrière.
Route::post('/langue', LocaleController::class)
    ->middleware('not-a-page')
    ->name('locale.switch');

/*
 * Le témoin de la page de vente (T-219, T-220).
 *
 * L'inverse de la situation de T-219 : la variante **est** devenue l'accueil
 * le 8 septembre 2026, et c'est l'ancienne page qui vit ici. Elle reste
 * servie plutôt que supprimée parce qu'une variante mesurée contre une page
 * qui n'existe plus ne mesure rien, et que la mesure n'a pas encore eu lieu.
 *
 * Servie en `noindex, follow` avec une canonique vers l'accueil — deux pages
 * de vente indexées pour le même produit se prendraient leur propre trafic —,
 * et hors de tout plan de site.
 */
Route::get('/lp/temoin', [LandingController::class, 'temoin'])->name('lp.temoin');

/*
 * L'ancienne URL de la variante, devenue l'accueil.
 *
 * Une redirection permanente et non une suppression : des liens ont pu être
 * posés sur `/lp/histoire`, et un 404 perdrait le visiteur en même temps que
 * l'autorité que l'URL avait accumulée.
 */
Route::permanentRedirect('/lp/histoire', '/')->name('lp.structure');

// Le manifeste d'installation, rendu depuis les réglages de marque. Sans
// contrainte de domaine, pour rester de même origine que la page qui le cite.
//
// `not-a-page` : le navigateur le demande de lui-même, quand il le décide, et
// `StartSession` en ferait la « page précédente » — le `back()` du formulaire
// suivant y retournerait, et Inertia recevrait du JSON (T-231).
Route::get('/site.webmanifest', ManifestController::class)
    ->middleware('not-a-page')
    ->name('manifest');

// Le plan de site : neuf adresses fixes, celles qui doivent être explorées
// comme un ensemble (T-225). robots.txt le déclare.
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('/robots.txt', RobotsController::class)->name('robots');

// La fenêtre de bienvenue : une adresse contre un code de réduction (T-141).
// Bornée par adresse et par IP : une liste de contacts est une cible.
Route::post('/offre-de-bienvenue', WelcomeOfferController::class)
    ->middleware('throttle:welcome-offer')
    ->name('welcome_offer.claim');

/*
 * Le tunnel d'achat. Les cinq premières étapes sont ouvertes : le compte se
 * crée à la quatrième, et exiger une connexion avant reviendrait à demander
 * un mot de passe à quelqu'un qui ne sait pas encore ce qu'il achète.
 *
 * Les deux pages (`/acheter`, `/acheter/merci`) sont déclinées par langue
 * plus haut ; les écritures ci-dessous n'ont qu'une adresse, et leur langue
 * vient du témoin posé par la page (`SetLocale`).
 */
Route::post('/acheter/etape/{step}', [CheckoutController::class, 'store'])
    ->whereNumber('step')
    ->name('checkout.step');

// Le code de réduction se pose et se retire au récapitulatif. Borné : un
// code à huit signes se devine mal, et on ne laisse personne essayer.
Route::post('/acheter/code', [CheckoutController::class, 'applyCode'])
    ->middleware('throttle:discount-code')
    ->name('checkout.code.apply');

Route::delete('/acheter/code', [CheckoutController::class, 'removeCode'])->name('checkout.code.remove');

Route::post('/acheter/payer', [CheckoutController::class, 'pay'])
    ->middleware('auth')
    ->name('checkout.pay');

Route::middleware(['auth', 'verified'])->group(function (): void {
    // Le tableau de bord de l'Initiateur·rice est l'espace, pas la page
    // gabarit du kit. Le nom de route reste : les composants d'authentification
    // du kit le référencent encore.
    Route::redirect('dashboard', '/espace')->name('dashboard');
});

require __DIR__.'/initiator.php';
require __DIR__.'/settings.php';
