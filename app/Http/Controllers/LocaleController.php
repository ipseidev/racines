<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Locale;
use App\Http\Middleware\SetLocale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Le sélecteur de langue des pages sans adresse déclinée : l'espace, les
 * comptes, les pages à jeton. (Les pages publiques n'en ont pas besoin :
 * leur sélecteur est fait de liens vers la même page dans l'autre langue,
 * et c'est l'adresse qui pose le témoin.)
 *
 * Le choix est retenu par témoin, et sur le compte s'il y en a un — sans
 * quoi la préférence du compte, lue avant le témoin sur un appareil neuf,
 * dirait le contraire du dernier choix.
 */
final class LocaleController
{
    public function __invoke(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'locale' => ['required', 'string', Rule::in(array_map(
                static fn (Locale $locale): string => $locale->value,
                Locale::cases(),
            ))],
        ]);

        $locale = Locale::from($validated['locale']);
        $user = $request->user();

        if ($user !== null && $user->locale !== $locale) {
            $user->forceFill(['locale' => $locale])->save();
        }

        return back()->withCookie(SetLocale::cookie($locale));
    }
}
