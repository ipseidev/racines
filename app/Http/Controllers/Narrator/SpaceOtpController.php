<?php

declare(strict_types=1);

namespace App\Http\Controllers\Narrator;

use App\Enums\OtpPurpose;
use App\Exceptions\Domain\OtpExpired;
use App\Exceptions\Domain\OtpInvalid;
use App\Exceptions\Domain\OtpLocked;
use App\Exceptions\Domain\OtpThrottled;
use App\Models\Narrator;
use App\Models\OtpChallenge;
use App\Services\Tokens\OtpService;
use App\Support\SensitiveGrant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Response;

/**
 * Le code d'acte sensible, demandé depuis l'espace narrateur.
 *
 * Même mécanique que depuis un lien d'enregistrement, autre sujet : ici le
 * jeton porte une personne, pas une histoire. La page ne montre que la forme
 * masquée de la coordonnée — la donner en clair permettrait à qui détient le
 * lien de la relever.
 */
final readonly class SpaceOtpController
{
    public function __construct(private OtpService $otp) {}

    public function show(Request $request): Response
    {
        $narrator = self::narratorFor($request);
        $challenge = self::pendingChallengeFor($narrator);

        return inertia('narrator/OtpChallenge', [
            'sentToMasked' => $challenge?->sent_to_masked,
            'channel' => ($challenge === null ? OtpService::channelFor($narrator) : $challenge->channel)->value,
            'expiresAt' => $challenge?->expires_at->toIso8601String(),
            'locked' => $challenge?->isLocked() ?? false,
        ]);
    }

    public function send(Request $request): RedirectResponse
    {
        $narrator = self::narratorFor($request);

        try {
            $this->otp->challenge($narrator, OtpPurpose::SensitiveAct, OtpService::channelFor($narrator));
        } catch (OtpThrottled) {
            return back()->with('status', __('narrator.otp.already_sent'));
        }

        return back()->with('status', __('narrator.otp.sent'));
    }

    public function verify(Request $request): RedirectResponse
    {
        $narrator = self::narratorFor($request);

        $validated = $request->validate([
            'code' => ['required', 'string', 'digits:'.config('product.otp.length')],
        ]);

        $challenge = self::pendingChallengeFor($narrator);

        if ($challenge === null) {
            throw ValidationException::withMessages(['code' => __('narrator.otp.expired')]);
        }

        try {
            $issued = $this->otp->verify($challenge, (string) $validated['code']);
        } catch (OtpLocked) {
            throw ValidationException::withMessages(['code' => __('narrator.otp.locked')]);
        } catch (OtpExpired) {
            throw ValidationException::withMessages(['code' => __('narrator.otp.expired')]);
        } catch (OtpInvalid) {
            throw ValidationException::withMessages(['code' => __('narrator.otp.invalid')]);
        }

        return redirect()
            ->intended(url()->previous())
            ->withCookie(SensitiveGrant::cookie($issued->plain));
    }

    private static function narratorFor(Request $request): Narrator
    {
        $narrator = $request->attributes->get('token_subject');

        abort_unless($narrator instanceof Narrator, 404);

        return $narrator;
    }

    private static function pendingChallengeFor(Narrator $narrator): ?OtpChallenge
    {
        return OtpChallenge::query()
            ->where('narrator_id', $narrator->id)
            ->where('purpose', OtpPurpose::SensitiveAct->value)
            ->whereNull('verified_at')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();
    }
}
