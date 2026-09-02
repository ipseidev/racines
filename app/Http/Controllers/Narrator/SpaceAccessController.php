<?php

declare(strict_types=1);

namespace App\Http\Controllers\Narrator;

use App\Enums\OtpPurpose;
use App\Exceptions\Domain\OtpExpired;
use App\Exceptions\Domain\OtpInvalid;
use App\Exceptions\Domain\OtpLocked;
use App\Exceptions\Domain\OtpNotDeliverable;
use App\Exceptions\Domain\OtpThrottled;
use App\Models\Narrator;
use App\Models\OtpChallenge;
use App\Services\Tokens\OtpService;
use App\Support\Links;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Response;

/**
 * L'entrée de l'espace narrateur : un code, jamais un mot de passe.
 *
 * La page **ne dit pas** si la coordonnée saisie est connue. Une réponse
 * différente ferait de ce formulaire un annuaire : « ce numéro est-il chez
 * vous ? » se demanderait à la chaîne. Connue ou non, le narrateur lit la
 * même phrase, et le code ne part que si elle l'est.
 *
 * Le code part sur la coordonnée **déjà détenue** : on ne demande jamais à
 * une personne âgée de ressaisir une adresse, et un code envoyé à une adresse
 * fournie par le visiteur ne prouverait rien.
 */
final readonly class SpaceAccessController
{
    public function __construct(private OtpService $otp) {}

    public function show(): Response
    {
        return inertia('narrator/SpaceRequest', [
            'codeLength' => (int) config('product.otp.length'),
        ]);
    }

    public function request(Request $request): RedirectResponse
    {
        $identifier = self::identifier($request);
        $narrator = self::findNarrator($identifier);

        if ($narrator !== null) {
            try {
                $this->otp->challenge($narrator, OtpPurpose::NarratorSpace, OtpService::channelFor($narrator));
            } catch (OtpNotDeliverable) {
                // Aucune coordonnée exploitable : on reste silencieux côté
                // page, et le support le verra dans les journaux.
            } catch (OtpThrottled) {
                // Trois codes par heure suffisent. Le dire en une phrase :
                // quelqu'un qui a touché deux fois le bouton n'a pas à
                // tomber sur une erreur technique, et son code précédent est
                // toujours valable.
                return back()->with('status', __('narrator.space.request.already_sent'));
            }
        }

        return back()->with('status', __('narrator.space.request.sent'));
    }

    public function verify(Request $request): RedirectResponse
    {
        $identifier = self::identifier($request);

        $validated = $request->validate([
            'code' => ['required', 'string', 'digits:'.config('product.otp.length')],
        ]);

        $narrator = self::findNarrator($identifier);
        $challenge = $narrator === null ? null : self::pendingChallengeFor($narrator);

        if ($narrator === null || $challenge === null) {
            throw ValidationException::withMessages(['code' => __('narrator.otp.expired')]);
        }

        try {
            // Le service émet lui-même le jeton que le motif du défi
            // accorde : en émettre un second ici en laisserait un valable
            // sans que personne l'ait reçu.
            $issued = $this->otp->verify($challenge, (string) $validated['code']);
        } catch (OtpLocked) {
            throw ValidationException::withMessages(['code' => __('narrator.otp.locked')]);
        } catch (OtpExpired) {
            throw ValidationException::withMessages(['code' => __('narrator.otp.expired')]);
        } catch (OtpInvalid) {
            throw ValidationException::withMessages(['code' => __('narrator.otp.invalid')]);
        }

        // `Links` est le seul endroit qui décide de la forme d'une URL à
        // jeton (convention §9) : il porte le domaine court et son port.
        return redirect()->to(Links::narratorSpace($issued->plain));
    }

    private static function identifier(Request $request): string
    {
        $validated = $request->validate([
            'identifier' => ['required', 'string', 'max:255'],
        ]);

        return trim((string) $validated['identifier']);
    }

    /**
     * Reconnue au numéro ou au courriel, exactement — aucune recherche
     * approchante : deviner qui l'on cherche n'est pas une authentification.
     */
    private static function findNarrator(string $identifier): ?Narrator
    {
        return Narrator::query()
            ->where(function ($query) use ($identifier): void {
                $query->where('phone_e164', $identifier)
                    ->orWhere('email', $identifier);
            })
            ->first();
    }

    private static function pendingChallengeFor(Narrator $narrator): ?OtpChallenge
    {
        return OtpChallenge::query()
            ->where('narrator_id', $narrator->id)
            ->where('purpose', OtpPurpose::NarratorSpace->value)
            ->whereNull('verified_at')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();
    }
}
