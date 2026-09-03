<?php

declare(strict_types=1);

namespace App\Http\Controllers\Checkout;

use App\Actions\SaveCheckoutStep;
use App\Actions\StartStripeCheckout;
use App\Enums\AddressForm;
use App\Enums\Channel;
use App\Features\GiftExperience;
use App\Features\PhoneOptionOffer;
use App\Features\PreventePrice;
use App\Models\CheckoutDraft;
use App\Settings\PilotSettings;
use App\Support\Options;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * Le tunnel d'achat, en six étapes.
 *
 * Six étapes et non une longue page : la quatrième crée un compte, et
 * quelqu'un qui abandonne à la cinquième ne doit pas tout ressaisir. Le
 * brouillon vit sept jours, dans un cookie anonyme puis rattaché au compte.
 *
 * L'étape franchie est mémorisée mais on ne recule jamais : revenir corriger
 * un champ ne fait pas perdre la suite.
 */
final readonly class CheckoutController
{
    public const DRAFT_COOKIE = 'checkout_draft';

    public function __construct(
        private SaveCheckoutStep $steps,
        private StartStripeCheckout $checkout,
    ) {}

    public function show(Request $request): Response
    {
        $draft = self::draftFor($request);
        $settings = app(PilotSettings::class);

        $requested = (int) $request->query('step', (string) $draft->step);
        $step = max(1, min(SaveCheckoutStep::LAST_STEP, $requested));

        return inertia('public/Checkout', [
            'step' => $step,
            'draft' => $draft->payload,
            'mode' => $settings->mode,
            'prices' => [
                'main' => $settings->isPrevente()
                    ? ($draft->price_variant ?? PreventePrice::forRequest($request))
                    : $settings->pilot_price_cents,
                'extraCopy' => $settings->extra_copy_price_cents,
                'phoneOption' => $settings->phone_option_price_cents,
            ],
            'phoneOption' => [
                'open' => PhoneOptionOffer::isOpen(),
                'remaining' => PhoneOptionOffer::remaining(),
                'cap' => PhoneOptionOffer::cap(),
            ],
            'giftVariant' => app(GiftExperience::class)->resolve(),
            'channels' => Options::of(Channel::class),
            'addressForms' => Options::of(AddressForm::class),
            'missingSteps' => SaveCheckoutStep::missingSteps($draft),
            'isAuthenticated' => $request->user() !== null,
        ]);
    }

    public function store(Request $request, int $step): RedirectResponse
    {
        $draft = self::draftFor($request);
        $rules = SaveCheckoutStep::rulesFor($step);

        $validated = $rules === [] ? [] : $request->validate($rules);

        $this->steps->handle($draft, $step, $validated);

        return redirect()
            ->route('checkout.show', ['step' => min(SaveCheckoutStep::LAST_STEP, $step + 1)])
            ->withCookie(self::draftCookie($draft));
    }

    public function pay(Request $request): RedirectResponse
    {
        $draft = self::draftFor($request);
        $buyer = $request->user();

        abort_if($buyer === null, 403);

        // Revalidation complète avant le paiement : quelqu'un qui a modifié
        // un champ puis sauté au récapitulatif ne doit pas passer avec une
        // valeur devenue invalide.
        $missing = SaveCheckoutStep::missingSteps($draft);

        if ($missing !== []) {
            return redirect()->route('checkout.show', ['step' => min($missing)]);
        }

        $session = $this->checkout->handle($draft, $buyer);

        return redirect()->away($session->url);
    }

    public function thanks(Request $request): Response
    {
        return inertia('public/CheckoutThanks', [
            'sessionId' => $request->query('session_id'),
        ]);
    }

    /**
     * Le brouillon de ce visiteur : celui de son compte, sinon celui de son
     * cookie, sinon un neuf.
     */
    private static function draftFor(Request $request): CheckoutDraft
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

        $id = $request->cookie(self::DRAFT_COOKIE);

        if (is_string($id) && $id !== '') {
            $draft = CheckoutDraft::query()->whereKey($id)->first();

            if ($draft instanceof CheckoutDraft && ! $draft->isExpired()) {
                // Rattaché au compte dès qu'il en a un : le brouillon suit la
                // personne, pas le navigateur.
                if ($user !== null && $draft->user_id === null) {
                    $draft->user()->associate($user);
                    $draft->save();
                }

                return $draft;
            }
        }

        $draft = new CheckoutDraft([
            'step' => 1,
            'payload' => [],
            'price_variant' => PreventePrice::forRequest($request),
            'expires_at' => now()->addDays(CheckoutDraft::LIFETIME_DAYS),
        ]);

        if ($user !== null) {
            $draft->user()->associate($user);
        }

        $draft->save();

        return $draft;
    }

    private static function draftCookie(CheckoutDraft $draft): Cookie
    {
        return cookie(
            name: self::DRAFT_COOKIE,
            value: $draft->id,
            minutes: CheckoutDraft::LIFETIME_DAYS * 24 * 60,
            httpOnly: true,
        );
    }
}
