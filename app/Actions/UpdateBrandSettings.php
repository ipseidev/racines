<?php

declare(strict_types=1);

namespace App\Actions;

use App\Settings\BrandSettings;
use App\Support\Contrast;
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
    private const NULLABLE = ['support_phone', 'logo_path', 'favicon_path'];

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
     * @return array<string, array<int, string>>
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
            'logo_path' => ['sometimes', 'nullable', 'string', 'max:255'],
            'favicon_path' => ['sometimes', 'nullable', 'string', 'max:255'],
            'legal_entity' => ['sometimes', 'nullable', 'string', 'max:120'],
            'legal_address' => ['sometimes', 'nullable', 'string', 'max:255'],
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
        ];
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
