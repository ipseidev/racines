<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\Locale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Lang;

/**
 * Traductions envoyées au front.
 *
 * On ne pousse que le fichier de l'espace courant plus le fichier commun :
 * les pages narrateur et famille doivent rester légères, elles sont ouvertes
 * en 4G sur de vieux téléphones (PRD US-01).
 *
 * Dans la langue de la requête (`app()->getLocale()`), avec le français en
 * dessous : une clé qui manquerait dans `lang/it` s'affiche en français
 * plutôt qu'en clé brute. Le test de parité (`I18nKeysTest`) interdit qu'il
 * en manque ; ceci est le filet, pas la règle.
 */
final class Translations
{
    private const COMMON = 'common';

    /**
     * Préfixe de nom de route vers fichier de langue.
     *
     * Les pages de compte n'ont pas de préfixe commun : Fortify nomme ses
     * routes `login`, `register`, `password.*`, `verification.*`,
     * `two-factor.*`. Elles sont énumérées, et partagent un seul fichier avec
     * les réglages du compte (`profile.*`, `security.*`, `passkeys.*`).
     */
    private const SPACES = [
        'narrator.' => 'narrator',
        'family.' => 'family',
        'initiator.' => 'initiator',
        'login' => 'auth',
        'register' => 'auth',
        'password.' => 'auth',
        'verification.' => 'auth',
        'two-factor.' => 'auth',
        'profile.' => 'auth',
        'security.' => 'auth',
        'user-password.' => 'auth',
        'passkeys.' => 'auth',
    ];

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function forRequest(Request $request): array
    {
        return self::forSpace(self::spaceFor($request));
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function forSpace(string $space, ?string $language = null): array
    {
        $language ??= app()->getLocale();
        $translations = [self::COMMON => self::file(self::COMMON, $language)];

        if ($space !== self::COMMON) {
            $translations[$space] = self::file($space, $language);
        }

        return $translations;
    }

    private static function spaceFor(Request $request): string
    {
        // Sans le préfixe de locale : `it.checkout.show` est une page
        // publique comme `checkout.show`.
        $name = LocalizedRoutes::baseName($request->route()?->getName()) ?? '';

        foreach (self::SPACES as $prefix => $space) {
            if (str_starts_with($name, $prefix)) {
                return $space;
            }
        }

        return 'public';
    }

    /**
     * @return array<string, mixed>
     */
    private static function file(string $name, string $language): array
    {
        $lines = self::lines($name, $language);
        $fallback = Locale::default()->language();

        if ($language === $fallback) {
            return $lines;
        }

        return array_replace_recursive(self::lines($name, $fallback), $lines);
    }

    /**
     * @return array<string, mixed>
     */
    private static function lines(string $name, string $language): array
    {
        $lines = Lang::get($name, [], $language, false);

        return is_array($lines) ? $lines : [];
    }
}
