<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Features\PreventePrice;
use App\Http\Controllers\Public\WelcomeOfferController as WelcomeOffer;
use App\Settings\PilotSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Cookie as SymfonyCookie;

/**
 * La page d'accueil, le témoin, « Comment ça marche » et la démonstration.
 *
 * **L'accueil a changé de page le 8 septembre 2026 (T-220)** : la variante de
 * structure de T-219 a pris sa place, et l'ancienne page est servie à
 * `/lp/temoin`, hors index. Elle n'est pas supprimée parce qu'une variante
 * sans témoin ne se mesure plus, et que la mesure prévue par T-219 n'a pas
 * eu lieu.
 *
 * Conséquence à connaître : l'ordre des sections du dossier 01 §4 — la
 * promesse, comment ça marche, l'essai en soixante secondes, le livre, les
 * engagements, le prix, les questions — est celui du **témoin**, et non plus
 * celui de l'accueil. L'accueil suit la succession commerciale de T-219, en
 * vingt-deux sections. La règle qui survit aux deux, elle, n'a pas bougé : on
 * explique avant de demander, comme sur la page d'enregistrement où l'on
 * explique avant de demander le micro.
 *
 * Le prix affiché dépend du mode et, en prévente, de la variante vue par ce
 * visiteur. Le cookie est posé **ici** : l'affectation doit précéder l'achat,
 * et un prix qui change entre la découverte et le paiement fait fuir.
 */
final class LandingController
{
    public function __invoke(Request $request): Response
    {
        return inertia('public/Landing', $this->salesPage($request));
    }

    /**
     * Le témoin, à `/lp/temoin` (T-220).
     *
     * L'ancienne page d'accueil, gardée servie pour qu'un test reste possible :
     * une variante mesurée contre une page qui n'existe plus ne mesure rien.
     * Hors index par le garde de `app.blade.php`, qui vise tout composant
     * `public/Landing…` sauf `public/Landing` — deux pages de vente indexées
     * pour la même offre se disputeraient leur propre trafic.
     */
    public function temoin(Request $request): Response
    {
        return inertia('public/LandingTemoin', $this->storefront($request));
    }

    /**
     * « Comment ça marche », en six étapes (T-213).
     *
     * Les mêmes props que l'accueil, et c'est voulu : la page affiche le prix,
     * fait écouter le même extrait et propose la même réduction de bienvenue.
     * Deux pages qui calculeraient le prix chacune de leur côté finiraient par
     * en afficher deux.
     */
    public function howItWorks(Request $request): Response
    {
        return inertia('public/HowItWorks', $this->storefront($request));
    }

    /**
     * Ce que toute page de vente reçoit : le mode, le prix vu par ce visiteur,
     * la fenêtre de bienvenue et l'extrait du héros.
     *
     * @return array<string, mixed>
     */
    private function storefront(Request $request): array
    {
        $settings = app(PilotSettings::class);
        $variant = PreventePrice::forRequest($request);

        // Mis en file plutôt qu'attaché : une réponse Inertia n'expose pas
        // ses en-têtes ici, et la file du framework les ajoute de toute façon.
        Cookie::queue(self::variantCookie($request));

        return [
            'mode' => $settings->mode,
            'price' => $settings->isPrevente() ? $variant : $settings->pilot_price_cents,
            'legalValidated' => $settings->legalValidated(),
            // La fenêtre de bienvenue (T-141). Pas à qui a déjà son code :
            // le cookie le dit, et proposer deux fois la même réduction à la
            // même personne ressemble à une relance.
            'welcomeOffer' => [
                'enabled' => $settings->welcomeOfferActive() && ! $request->hasCookie(WelcomeOffer::COOKIE),
                'discountPercent' => $settings->welcome_offer_discount_percent,
            ],
            'heroSample' => self::heroSample(),
        ];
    }

    /**
     * Ce que l'accueil reçoit en plus du fond de boutique (T-219, T-220).
     *
     * Les cinq collections de preuves, vides par défaut : c'est le vide qui
     * choisit le repli de chaque section, et un repli se déclare comme
     * démonstration. Remplir une collection remplace le repli ; rien ne se
     * remplit tout seul.
     *
     * `/lp/histoire` ne passe plus par ici : la route redirige en 301 vers
     * `/`, pour que les liens déjà posés sur la variante continuent de mener
     * à la page qu'ils visaient.
     *
     * @return array<string, mixed>
     */
    private function salesPage(Request $request): array
    {
        return [
            ...$this->storefront($request),
            'variant' => (string) config('product.landing.structure.id'),
            'proof' => [
                'press' => self::proof('press'),
                'quotes' => self::proof('quotes'),
                'reviews' => self::proof('reviews'),
                'videos' => self::proof('videos'),
                'stories' => self::proof('stories'),
            ],
        ];
    }

    /**
     * Une collection de preuves, réduite à des chaînes.
     *
     * Le réglage est du PHP libre : tout ce qui n'est pas une entrée en forme
     * de tableau de chaînes est **écarté silencieusement**, et une collection
     * qui se vide ainsi retombe sur son repli. Une preuve à demi lisible ne
     * doit pas pouvoir s'afficher à demi.
     *
     * @return list<array<string, string>>
     */
    private static function proof(string $key): array
    {
        $items = config('product.landing.structure.'.$key);

        if (! is_array($items)) {
            return [];
        }

        $collection = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $entry = [];

            foreach ($item as $field => $value) {
                if (is_string($field) && (is_string($value) || is_int($value))) {
                    $entry[$field] = (string) $value;
                }
            }

            if ($entry !== []) {
                $collection[] = $entry;
            }
        }

        return $collection;
    }

    /**
     * La page « Questions fréquentes » (T-222).
     *
     * Les mêmes props que l'accueil — la barre affiche le prix, le pied de
     * page la réduction —, plus le prix du livre numérique, seul chiffre cité
     * dans les réponses que la mise en page ne partage pas déjà.
     */
    public function faq(Request $request): Response
    {
        return inertia('public/Faq', [
            ...$this->storefront($request),
            'ebookPrice' => app(PilotSettings::class)->ebook_price_cents,
        ]);
    }

    public function demo(): Response
    {
        return inertia('public/Demo', [
            'limits' => [
                'demoSeconds' => 60,
                'segmentMilliseconds' => (int) config('product.recording.segment_milliseconds'),
                'acceptedMimes' => array_values((array) config('product.recording.accepted_mimes')),
            ],
        ]);
    }

    /**
     * L'extrait qu'on peut écouter dans la carte du héros (T-149).
     *
     * Rendu seulement si le fichier est là : une page d'accueil qui affiche un
     * bouton « Écouter » au-dessus d'un fichier absent est pire que pas de
     * bouton du tout. Absent, la carte reprend sa frise décorative.
     *
     * `disclosed` voyage avec l'extrait : c'est un choix de page, et le
     * composant ne doit pas avoir à deviner ce qu'on veut afficher sous le
     * bouton.
     *
     * @return array{src: string, disclosed: bool}|null
     */
    private static function heroSample(): ?array
    {
        $path = (string) config('product.landing.hero_sample');

        if ($path === '' || ! is_file(public_path($path))) {
            return null;
        }

        return [
            'src' => '/'.ltrim($path, '/'),
            'disclosed' => (bool) config('product.landing.hero_sample_disclosed'),
        ];
    }

    /**
     * Le cookie d'affectation, posé à la première visite et gardé quatre-vingt-
     * dix jours. Anonyme : il ne contient qu'un identifiant tiré au hasard.
     */
    private static function variantCookie(Request $request): SymfonyCookie
    {
        $existing = $request->cookie(PreventePrice::COOKIE);

        return cookie(
            name: PreventePrice::COOKIE,
            value: is_string($existing) && $existing !== '' ? $existing : (string) Str::uuid7(),
            minutes: PreventePrice::COOKIE_DAYS * 24 * 60,
            httpOnly: true,
        );
    }
}
