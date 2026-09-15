<?php

declare(strict_types=1);

namespace App\Actions;

use App\Settings\BrandSettings;
use App\Support\Contrast;
use Closure;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Met à jour la marque après validation.
 *
 * Deux règles ne se négocient pas : l'expéditeur SMS doit être accepté par les
 * opérateurs (doc 04 §9), et aucune combinaison de couleurs illisible ne peut
 * être enregistrée (PRD US-06, seuil AA 4,5:1).
 */
final class UpdateBrandSettings
{
    private const HEX = 'regex:/^#[0-9a-fA-F]{6}$/';

    /** Propriétés du réglage qui acceptent réellement null. */
    private const NULLABLE = ['support_phone', 'mark_path', 'logo_path', 'favicon_path'];

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(array $attributes): BrandSettings
    {
        $brand = app(BrandSettings::class);

        $validated = Validator::make($attributes, $this->rules(), $this->messages())->validate();

        $this->assertColoursAreReadable($brand, $validated);

        foreach ($validated as $property => $value) {
            // Filament renvoie null pour un champ texte vidé ; les propriétés
            // non nullables du réglage attendent une chaîne.
            if ($value === null && ! in_array($property, self::NULLABLE, true)) {
                $value = '';
            }

            $brand->{$property} = $value;
        }

        $brand->save();

        return $brand;
    }

    /**
     * @return array<string, array<int, string|Closure>>
     */
    private function rules(): array
    {
        $colours = [];

        foreach ([
            'color_primary', 'color_primary_foreground',
            'color_accent', 'color_accent_foreground',
            'color_background', 'color_surface',
            'color_text', 'color_muted',
        ] as $key) {
            $colours[$key] = ['sometimes', 'string', self::HEX];
        }

        return [
            'product_name' => ['sometimes', 'string', 'min:2', 'max:60'],
            'short_name' => ['sometimes', 'string', 'min:2', 'max:30'],
            'tagline' => ['sometimes', 'nullable', 'string', 'max:200'],
            'links_domain' => ['sometimes', 'string', 'max:120', 'regex:/^[a-z0-9.-]+$/'],
            'support_email' => ['sometimes', 'email', 'max:120'],
            'support_phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            // Expéditeur alphanumérique : 3 à 11 caractères, au moins une lettre.
            'sms_sender_id' => ['sometimes', 'string', 'regex:/^(?=.*[A-Za-z])[A-Za-z0-9]{3,11}$/'],
            'font_display' => ['sometimes', 'string', 'max:60'],
            'font_body' => ['sometimes', 'string', 'max:60'],
            'mark_path' => ['sometimes', 'nullable', 'string', 'max:255'],
            'logo_path' => ['sometimes', 'nullable', 'string', 'max:255'],
            'favicon_path' => ['sometimes', 'nullable', 'string', 'max:255'],
            /*
             * L'identité de l'éditeur : jamais vide (T-242). Une chaîne vide
             * ne fait pas échouer une page, elle l'ampute — « Le représentant
             * légal de . » — et personne ne relit ses propres mentions
             * légales. `required` est donc ici une règle de conformité, pas
             * de confort.
             */
            'legal_entity' => ['sometimes', 'required', 'string', 'max:120'],
            'legal_form' => ['sometimes', 'required', 'string', 'max:120'],
            'legal_address' => ['sometimes', 'required', 'string', 'max:255'],
            'legal_siren' => ['sometimes', 'required', 'string', 'max:20', $this->immatriculation(9)],
            'legal_siret' => ['sometimes', 'required', 'string', 'max:25', $this->immatriculation(14)],
            'legal_vat' => ['sometimes', 'required', 'string', 'max:20'],
            'legal_publication_director' => ['sometimes', 'required', 'string', 'max:120'],
            'legal_host' => ['sometimes', 'required', 'string', 'max:255'],
            'legal_host_media' => ['sometimes', 'required', 'string', 'max:255'],
            'legal_host_location' => ['sometimes', 'required', 'string', 'max:120'],
            ...$colours,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function messages(): array
    {
        return [
            'sms_sender_id.regex' => 'L’expéditeur SMS doit comporter 3 à 11 caractères alphanumériques, dont au moins une lettre.',
            'links_domain.regex' => 'Le domaine ne peut contenir que des minuscules, des chiffres, des points et des tirets.',
            'legal_entity.required' => 'La raison sociale est obligatoire : sans elle, les mentions légales ne nomment personne.',
            'legal_form.required' => 'La forme juridique est obligatoire ; un entrepreneur individuel doit faire suivre son nom de « EI ».',
            'legal_address.required' => 'L’adresse de l’éditeur est obligatoire (LCEN, article 6 III-1).',
            'legal_siren.required' => 'Le numéro SIREN est obligatoire (LCEN, article 6 III-1).',
            'legal_siret.required' => 'Le numéro SIRET du siège est obligatoire.',
            'legal_vat.required' => 'Le numéro de TVA intracommunautaire est obligatoire pour une vente en ligne (LCEN, article 19).',
            'legal_publication_director.required' => 'Le directeur ou la directrice de la publication est obligatoire (LCEN, article 6 III-1 c).',
            'legal_host.required' => 'L’hébergeur doit être nommé, avec son adresse et son téléphone : « communiqué sur demande » ne satisfait pas la LCEN.',
            'legal_host_media.required' => 'L’hébergeur des médias doit être nommé lui aussi : c’est là que vivent les enregistrements.',
            'legal_host_location.required' => 'Le lieu des serveurs est l’engagement d’hébergement européen : il doit être écrit.',
        ];
    }

    /**
     * Un numéro d'immatriculation français : le bon nombre de chiffres, et la
     * clé de Luhn qui les vérifie.
     *
     * Un chiffre transposé dans un SIREN donne un numéro d'apparence valide
     * qui désigne une autre entreprise ou personne. La clé le refuse, et c'est
     * le seul contrôle qu'on puisse faire sans interroger l'INSEE.
     */
    private function immatriculation(int $chiffres): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($chiffres): void {
            $digits = preg_replace('/\s+/', '', is_string($value) ? $value : '') ?? '';

            if (preg_match('/^\d{'.$chiffres.'}$/', $digits) !== 1) {
                $fail(sprintf('Ce numéro doit comporter %d chiffres.', $chiffres));

                return;
            }

            if (! self::luhn($digits)) {
                $fail('La clé de contrôle de ce numéro est fausse : vérifiez la saisie.');
            }
        };
    }

    /**
     * La clé de Luhn, celle qui valide un SIREN comme un SIRET.
     */
    private static function luhn(string $digits): bool
    {
        $sum = 0;
        $double = false;

        for ($i = strlen($digits) - 1; $i >= 0; $i--) {
            $digit = (int) $digits[$i];

            if ($double) {
                $digit *= 2;

                if ($digit > 9) {
                    $digit -= 9;
                }
            }

            $sum += $digit;
            $double = ! $double;
        }

        return $sum % 10 === 0;
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function assertColoursAreReadable(BrandSettings $brand, array $validated): void
    {
        $errors = [];

        foreach (BrandSettings::contrastPairs() as [$foregroundKey, $backgroundKey, $label]) {
            $foreground = $validated[$foregroundKey] ?? $brand->{$foregroundKey};
            $background = $validated[$backgroundKey] ?? $brand->{$backgroundKey};

            if (! array_key_exists($foregroundKey, $validated) && ! array_key_exists($backgroundKey, $validated)) {
                continue;
            }

            try {
                $ratio = Contrast::ratio((string) $foreground, (string) $background);
            } catch (Throwable) {
                continue;
            }

            if ($ratio < Contrast::AA_NORMAL_TEXT) {
                $errors[$foregroundKey][] = sprintf(
                    'Contraste insuffisant pour le %s : %s:1, il en faut au moins %s:1.',
                    $label,
                    $ratio,
                    Contrast::AA_NORMAL_TEXT,
                );
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }
}
