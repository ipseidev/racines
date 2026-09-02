<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Lang;

/**
 * Traductions envoyées au front.
 *
 * On ne pousse que le fichier de l'espace courant plus le fichier commun :
 * les pages narrateur et famille doivent rester légères, elles sont ouvertes
 * en 4G sur de vieux téléphones (PRD US-01).
 */
final class Translations
{
    private const COMMON = 'common';

    /** Préfixe de nom de route vers fichier de langue. */
    private const SPACES = [
        'narrator.' => 'narrator',
        'family.' => 'family',
        'initiator.' => 'initiator',
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
    public static function forSpace(string $space): array
    {
        $translations = [self::COMMON => self::file(self::COMMON)];

        if ($space !== self::COMMON) {
            $translations[$space] = self::file($space);
        }

        return $translations;
    }

    private static function spaceFor(Request $request): string
    {
        $name = $request->route()?->getName() ?? '';

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
    private static function file(string $name): array
    {
        $lines = Lang::get($name);

        return is_array($lines) ? $lines : [];
    }
}
