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
 * La page d'accueil, et la démonstration.
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
        $settings = app(PilotSettings::class);
        $variant = PreventePrice::forRequest($request);

        // Mis en file plutôt qu'attaché : une réponse Inertia n'expose pas
        // ses en-têtes ici, et la file du framework les ajoute de toute façon.
        Cookie::queue(self::variantCookie($request));

        return inertia('public/Landing', [
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
