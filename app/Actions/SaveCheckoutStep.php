<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AddressForm;
use App\Enums\Cadence;
use App\Enums\Channel;
use App\Enums\TechComfort;
use App\Features\GiftExperience;
use App\Features\PhoneOptionOffer;
use App\Models\CheckoutDraft;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

/**
 * Une étape du tunnel, validée puis rangée dans le brouillon.
 *
 * Les règles vivent ici et non dans le contrôleur pour une raison précise :
 * l'étape 6 doit pouvoir **revalider l'ensemble** avant d'ouvrir le paiement.
 * Quelqu'un qui revient en arrière modifier son numéro, puis saute au
 * récapitulatif, ne doit pas passer avec un champ devenu invalide.
 */
final class SaveCheckoutStep
{
    public const LAST_STEP = 6;

    /**
     * Les règles de validation d'une étape.
     *
     * @return array<string, mixed>
     */
    public static function rulesFor(int $step, ?CheckoutDraft $draft = null): array
    {
        // Raconter soi-même change deux choses (T-136) : on ne demande pas à
        // quel point on est à l'aise avec son propre téléphone, et il n'y a
        // pas de mot à joindre à une invitation qu'on s'envoie.
        $self = $draft?->value('for') === 'self';

        return match ($step) {
            1 => [
                'for' => ['required', Rule::in(['relative', 'self'])],
            ],
            2 => [
                'narrator_first_name' => ['required', 'string', 'max:80'],
                'narrator_last_name' => ['nullable', 'string', 'max:80'],
                'relationship' => ['nullable', 'string', 'max:40'],
                // Au moins une coordonnée : sans elle, l'invitation n'a nulle
                // part où partir, et le projet meurt avant de commencer.
                'narrator_email' => ['nullable', 'email:rfc', 'required_without:narrator_phone'],
                'narrator_phone' => ['nullable', 'string', 'regex:/^\+[1-9]\d{7,14}$/', 'required_without:narrator_email'],
                'preferred_channel' => ['required', new Enum(Channel::class)],
                'address_form' => ['required', new Enum(AddressForm::class)],
                'narrator_tech_comfort' => $self
                    ? ['nullable', new Enum(TechComfort::class)]
                    : ['required', new Enum(TechComfort::class)],
            ],
            3 => [
                // Demain par défaut, quatre-vingt-dix jours au plus : au-delà,
                // l'acheteur aurait oublié ce qu'il a commandé.
                'gift_send_at' => ['required', 'date', 'after_or_equal:today', 'before_or_equal:'.now()->addDays(90)->toDateString()],
                'gift_send_time' => ['required', 'date_format:H:i'],
                'gift_message' => $self
                    ? ['nullable', 'string', 'max:600']
                    : ['required', 'string', 'max:600'],
                // La forme du cadeau n'est plus demandée : une seule est livrée
                // (T-108). Le drapeau décide, et la valeur reste acceptée si
                // un ancien brouillon la porte.
                'gift_variant' => ['sometimes', Rule::in(GiftExperience::variants())],
            ],
            5 => [
                'extra_copies' => ['required', 'integer', 'min:0', 'max:5'],
                'phone_option' => ['sometimes', 'boolean'],
                // Obligatoire, et séparée des deux autres : mêler les CGV au
                // marketing dans une seule case ne vaut pas consentement.
                'accepts_terms' => ['accepted'],
                'early_service_start' => ['sometimes', 'boolean'],
                'marketing_email' => ['sometimes', 'boolean'],
            ],
            default => [],
        };
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function handle(CheckoutDraft $draft, int $step, array $values): CheckoutDraft
    {
        if ($step === 3) {
            $values['gift_variant'] ??= app(GiftExperience::class)->resolve();
        }

        if ($step === 5) {
            // Le plafond est appliqué **côté serveur** : masquer la case ne
            // suffit pas, un formulaire se rejoue (critère de sortie §8).
            $values['phone_option'] = ($values['phone_option'] ?? false) && PhoneOptionOffer::isOpen();
        }

        return $draft->merge($values, $step + 1);
    }

    /**
     * Le brouillon est-il complet ?
     *
     * @return list<int> Les étapes qui manquent.
     */
    public static function missingSteps(CheckoutDraft $draft): array
    {
        $missing = [];

        foreach ([1, 2, 3, 5] as $step) {
            $stepRules = self::rulesFor($step, $draft);

            foreach ($stepRules as $field => $rules) {
                $required = is_array($rules) && (
                    in_array('required', $rules, true) || in_array('accepted', $rules, true)
                );

                if ($required && $draft->value($field) === null) {
                    $missing[] = $step;

                    break;
                }
            }
        }

        return array_values(array_unique($missing));
    }

    /**
     * La cadence par défaut : hebdomadaire. Le narrateur pourra la changer à
     * l'opt-in, et le moteur la proposera s'il ralentit.
     */
    public static function defaultCadence(): Cadence
    {
        return Cadence::Weekly;
    }
}
