<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasTranslatedLabel;

/**
 * Les langues et marchés que l'interface sait servir.
 *
 * Une locale est **une langue et un marché** : `it-CH` parle italien et
 * formate ses dates et ses prix à la suisse. Les traductions, elles, ne
 * connaissent que la langue (`lang/it`) : `language()` est ce que reçoit
 * `App::setLocale()`, et `tag()` ce que reçoivent `lang=` et `Intl`.
 *
 * Le français est la locale par défaut et vit à la racine des adresses ; les
 * autres portent un préfixe (`/it/…`, `/fr-ch/…`) sur les pages publiques
 * (`LocalizedRoutes`). L'allemand n'y est pas : une famille suisse
 * alémanique est servie en français de Suisse, faute de mieux, et
 * `fromAcceptLanguage()` le dit explicitement.
 *
 * Les cinq valeurs sont aussi les seules acceptées par les colonnes
 * `users.locale` et `projects.locale` (contrainte `check`).
 */
enum Locale: string
{
    use HasTranslatedLabel;

    case French = 'fr';
    case Italian = 'it';
    case Spanish = 'es';
    case SwissFrench = 'fr-CH';
    case SwissItalian = 'it-CH';

    public static function default(): self
    {
        return self::French;
    }

    /** La langue des traductions : `fr`, `it` ou `es`. */
    public function language(): string
    {
        return match ($this) {
            self::French, self::SwissFrench => 'fr',
            self::Italian, self::SwissItalian => 'it',
            self::Spanish => 'es',
        };
    }

    public function market(): Market
    {
        return match ($this) {
            self::French => Market::France,
            self::Italian => Market::Italy,
            self::Spanish => Market::Spain,
            self::SwissFrench, self::SwissItalian => Market::Switzerland,
        };
    }

    public function currency(): Currency
    {
        return $this->market()->currency();
    }

    /** L'étiquette BCP 47 complète, pour `Intl` : `fr-FR`, `it-CH`… */
    public function tag(): string
    {
        return $this->language().'-'.$this->market()->value;
    }

    /** La forme d'Open Graph : `fr_FR`, `it_CH`… */
    public function openGraph(): string
    {
        return $this->language().'_'.$this->market()->value;
    }

    /** Le nom de la langue dans la langue elle-même : c'est ainsi qu'on la choisit. */
    public function nativeName(): string
    {
        return match ($this) {
            self::French => 'Français',
            self::Italian => 'Italiano',
            self::Spanish => 'Español',
            self::SwissFrench => 'Français (Suisse)',
            self::SwissItalian => 'Italiano (Svizzera)',
        };
    }

    /** Le segment d'adresse des pages publiques : rien pour le français. */
    public function urlPrefix(): ?string
    {
        return $this === self::default() ? null : strtolower($this->value);
    }

    /** Le préfixe des noms de route : `''` pour le français, `it.`, `fr-ch.`… */
    public function routePrefix(): string
    {
        $prefix = $this->urlPrefix();

        return $prefix === null ? '' : $prefix.'.';
    }

    /** La locale de la page de paiement Stripe, qui ne connaît que la langue. */
    public function stripe(): string
    {
        return $this->language();
    }

    public static function fromUrlPrefix(string $segment): ?self
    {
        $segment = strtolower($segment);

        foreach (self::cases() as $case) {
            if ($case->urlPrefix() === $segment) {
                return $case;
            }
        }

        return null;
    }

    /**
     * Lit une valeur stockée ou reçue : `fr-CH`, `fr_ch`, `IT`… ou rien.
     */
    public static function tryFromTag(mixed $value): ?self
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        $normalized = str_replace('_', '-', trim($value));

        foreach (self::cases() as $case) {
            if (strcasecmp($case->value, $normalized) === 0) {
                return $case;
            }
        }

        return null;
    }

    /**
     * La meilleure locale d'après l'en-tête `Accept-Language`, ou rien.
     *
     * Par ordre de préférence du navigateur : une étiquette entière d'abord
     * (`it-CH`), puis la langue seule (`it`), puis la région seule — un
     * navigateur en `de-CH` est servi en français de Suisse.
     */
    public static function fromAcceptLanguage(?string $header): ?self
    {
        if ($header === null || trim($header) === '') {
            return null;
        }

        $preferences = [];

        foreach (explode(',', $header) as $index => $part) {
            $pieces = array_map('trim', explode(';', $part));
            $tag = strtolower((string) array_shift($pieces));

            if ($tag === '' || $tag === '*') {
                continue;
            }

            $quality = 1.0;

            foreach ($pieces as $piece) {
                if (str_starts_with($piece, 'q=')) {
                    $quality = (float) substr($piece, 2);
                }
            }

            $preferences[] = ['tag' => $tag, 'q' => $quality, 'order' => $index];
        }

        usort($preferences, fn (array $a, array $b): int => [$b['q'], $a['order']] <=> [$a['q'], $b['order']]);

        foreach ($preferences as $preference) {
            $exact = self::fromUrlPrefix($preference['tag']) ?? self::tryFromTag($preference['tag']);

            if ($exact !== null) {
                return $exact;
            }
        }

        foreach ($preferences as $preference) {
            [$language, $region] = array_pad(explode('-', $preference['tag'], 2), 2, null);

            foreach (self::cases() as $case) {
                if ($case->language() === $language && ($region === null || strtolower($case->market()->value) === $region)) {
                    return $case;
                }
            }

            if ($region === 'ch') {
                return self::SwissFrench;
            }
        }

        foreach ($preferences as $preference) {
            $language = explode('-', $preference['tag'], 2)[0];

            foreach (self::cases() as $case) {
                if ($case->language() === $language) {
                    return $case;
                }
            }
        }

        return null;
    }
}
