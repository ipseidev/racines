<?php

declare(strict_types=1);

namespace App\Http\Controllers\Qr;

use App\Models\Story;
use App\Support\QrFamilyCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Vérifier le code famille d'un livre.
 *
 * **Cinq essais par heure et par livre**, comptés sur le jeton et non sur
 * l'adresse IP : un livre lu dans une maison de retraite passe par un seul
 * routeur, et compter par IP y bloquerait tout le monde au troisième lecteur
 * (même raison qu'en T-79).
 *
 * Le message d'échec ne dit pas combien d'essais restent : l'annoncer aide
 * surtout celui qui essaie de deviner.
 */
final class FamilyCodeController
{
    private const MAX_ATTEMPTS = 5;

    public function __invoke(Request $request, string $token): RedirectResponse
    {
        $story = $request->attributes->get('token_subject');

        if (! $story instanceof Story) {
            abort(404);
        }

        $project = $story->project;

        if (! QrFamilyCode::required($project)) {
            return redirect()->route('family.qr', ['token' => $token]);
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:64'],
        ]);

        $key = 'qr-code:'.hash('sha256', $token);

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            return back()->withErrors(['code' => __('family.qr.too_many')]);
        }

        if (! QrFamilyCode::matches($project, (string) $validated['code'])) {
            RateLimiter::hit($key, 3600);

            return back()->withErrors(['code' => __('family.qr.wrong_code')]);
        }

        RateLimiter::clear($key);

        return redirect()
            ->route('family.qr', ['token' => $token])
            ->withCookie(QrFamilyCode::unlockCookie($project));
    }
}
