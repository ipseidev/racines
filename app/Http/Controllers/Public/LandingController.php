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
 * La page d'accueil, la page « Comment ça marche » et la démonstration.
 *
 * L'ordre des sections vient du dossier 01 §4 et n'est pas négociable : la
 * promesse, comment ça marche, l'essai en soixante secondes, le livre, les
 * engagements, le prix, les questions. On explique avant de demander — c'est
 * la même règle que sur la page d'enregistrement, où l'on explique avant de
 * demander le micro.
 *
 * Le prix affiché dépend du mode et, en prévente, de la variante vue par ce
 * visiteur. Le cookie est posé **ici** : l'affectation doit précéder l'achat,
 * et un prix qui change entre la découverte et le paiement fait fuir.
 */
final class LandingController
{
    public function __invoke(Request $request): Response
    {
        return inertia('public/Landing', $this->storefront($request));
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
     * La variante de structure, à `/lp/histoire` (T-219).
     *
     * L'accueil reste le témoin : on ne remplace pas une page qui vend par une
     * page qu'on n'a pas encore mesurée. Mêmes props que l'accueil, plus les
     * cinq collections de preuves — vides par défaut, et c'est le vide qui
     * choisit le repli de chaque section.
     */
    public function structure(Request $request): Response
    {
        return inertia('public/LandingStructure', [
            ...$this->storefront($request),
            'variant' => (string) config('product.landing.structure.id'),
            'proof' => [
                'press' => self::proof('press'),
                'quotes' => self::proof('quotes'),
                'reviews' => self::proof('reviews'),
                'videos' => self::proof('videos'),
                'stories' => self::proof('stories'),
            ],
        ]);
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
