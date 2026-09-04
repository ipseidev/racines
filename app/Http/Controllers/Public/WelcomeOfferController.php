<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Actions\ClaimWelcomeOffer;
use App\Models\Lead;
use App\Settings\PilotSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Throwable;

/**
 * La fenêtre de bienvenue de la page d'accueil : une adresse contre un code
 * de réduction (T-141).
 *
 * Trois gardes, dans cet ordre. Un champ invisible que seul un robot
 * remplit : rempli, on répond « merci » sans rien garder, parce qu'un robot
 * qui reçoit une erreur apprend, et un robot qui reçoit un merci s'en va. La
 * borne `welcome-offer` par adresse et par IP. Et la validation ordinaire.
 *
 * Le code part par courriel et **pas à l'écran** : c'est ce qui fait qu'une
 * adresse laissée est une adresse qui existe. Il est aussi posé en cookie,
 * pour que la réduction s'applique toute seule si la commande se fait depuis
 * le même appareil.
 */
final readonly class WelcomeOfferController
{
    public const COOKIE = 'welcome_code';

    /** Le champ que personne ne voit et que personne ne remplit. */
    public const HONEYPOT = 'website';

    public function __construct(private ClaimWelcomeOffer $claim) {}

    public function __invoke(Request $request): RedirectResponse
    {
        if (! app(PilotSettings::class)->welcomeOfferActive()) {
            return back()->withErrors(['email' => __('public.welcome_offer.errors.closed')]);
        }

        if ((string) $request->input(self::HONEYPOT, '') !== '') {
            return back();
        }

        $validated = $request->validate([
            'email' => ['required', 'string', 'email:rfc', 'max:254'],
            'news' => ['sometimes', 'boolean'],
        ]);

        try {
            $lead = $this->claim->handle(
                email: (string) $validated['email'],
                wantsNews: (bool) ($validated['news'] ?? false),
                context: ['ip' => $request->ip(), 'user_agent' => $request->userAgent()],
            );
        } catch (Throwable $exception) {
            // Le code existe peut-être déjà en base : c'est l'envoi qui a
            // échoué. On le dit, et la prochaine tentative renverra le même.
            report($exception);

            return back()->withErrors(['email' => __('public.welcome_offer.errors.send_failed')]);
        }

        Cookie::queue(cookie(
            name: self::COOKIE,
            value: $lead->discount_code,
            minutes: Lead::CODE_LIFETIME_DAYS * 24 * 60,
            httpOnly: true,
        ));

        return back();
    }
}
