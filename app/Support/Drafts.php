<?php

declare(strict_types=1);

namespace App\Support;

use App\Features\PreventePrice;
use App\Models\CheckoutDraft;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * Le brouillon de commande de ce visiteur, où qu'il soit dans le parcours.
 *
 * Extrait du contrôleur du tunnel le jour où le tunnel de découverte est
 * arrivé : les deux écrivent dans le même brouillon, et deux copies de cette
 * logique auraient fini par diverger sur le point où elle coûte le plus cher
 * — le rattachement au compte. Quelqu'un qui répond au quiz depuis son
 * téléphone, puis se connecte, doit retrouver ses réponses.
 *
 * Le brouillon suit **la personne**, pas le navigateur : dès qu'un compte
 * existe, le brouillon du cookie lui est rattaché et c'est celui du compte
 * qui fait foi.
 */
final class Drafts
{
    public const COOKIE = 'checkout_draft';

    /** Le brouillon de ce visiteur, ou un neuf. */
    public static function open(Request $request): CheckoutDraft
    {
        $existing = self::current($request);

        if ($existing instanceof CheckoutDraft) {
            return $existing;
        }

        $draft = new CheckoutDraft([
            'step' => 1,
            'payload' => [],
            'price_variant' => PreventePrice::forRequest($request),
            'expires_at' => now()->addDays(CheckoutDraft::LIFETIME_DAYS),
        ]);

        $user = $request->user();

        if ($user !== null) {
            $draft->user()->associate($user);
        }

        $draft->save();

        return $draft;
    }

    /**
     * Le brouillon déjà là, sans en créer : celui du compte, sinon celui du
     * cookie.
     *
     * Sans création, parce que deux pages le demandent seulement pour
     * s'afficher — le remerciement, le premier écran du quiz — et qu'un
     * brouillon né d'un simple affichage n'encombrerait la table que pour
     * expirer sept jours plus tard.
     */
    public static function current(Request $request): ?CheckoutDraft
    {
        $user = $request->user();

        if ($user !== null) {
            $existing = CheckoutDraft::query()
                ->where('user_id', $user->id)
                ->where('expires_at', '>', now())
                ->latest()
                ->first();

            if ($existing instanceof CheckoutDraft) {
                return $existing;
            }
        }

        $id = $request->cookie(self::COOKIE);

        if (! is_string($id) || $id === '') {
            return null;
        }

        $draft = CheckoutDraft::query()->whereKey($id)->first();

        if (! $draft instanceof CheckoutDraft || $draft->isExpired()) {
            return null;
        }

        if ($user !== null && $draft->user_id === null) {
            $draft->user()->associate($user);
            $draft->save();
        }

        return $draft;
    }

    public static function cookie(CheckoutDraft $draft): Cookie
    {
        return cookie(
            name: self::COOKIE,
            value: $draft->id,
            minutes: CheckoutDraft::LIFETIME_DAYS * 24 * 60,
            httpOnly: true,
        );
    }
}
