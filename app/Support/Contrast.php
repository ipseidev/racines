<?php

declare(strict_types=1);

namespace App\Support;

use InvalidArgumentException;

/**
 * Rapport de contraste WCAG 2.2 entre deux couleurs.
 *
 * L'accessibilité est une exigence produit, pas une option : l'administration
 * refuse d'enregistrer une combinaison illisible (PRD US-06, seuil AA 4,5:1).
 */
final class Contrast
{
    public const AA_NORMAL_TEXT = 4.5;

    public const AA_LARGE_TEXT = 3.0;

    public static function ratio(string $first, string $second): float
    {
        $lighter = max(self::luminance($first), self::luminance($second));
        $darker = min(self::luminance($first), self::luminance($second));

        return round(($lighter + 0.05) / ($darker + 0.05), 2);
    }

    public static function isReadable(
        string $foreground,
        string $background,
        float $threshold = self::AA_NORMAL_TEXT,
    ): bool {
        return self::ratio($foreground, $background) >= $threshold;
    }

    /**
     * Luminance relative, formule WCAG.
     */
    private static function luminance(string $hex): float
    {
        [$red, $green, $blue] = self::channels($hex);

        return 0.2126 * self::linearize($red)
            + 0.7152 * self::linearize($green)
            + 0.0722 * self::linearize($blue);
    }

    private static function linearize(int $channel): float
    {
        $value = $channel / 255;

        return $value <= 0.03928
            ? $value / 12.92
            : (($value + 0.055) / 1.055) ** 2.4;
    }

    /**
     * @return array{int, int, int}
     */
    private static function channels(string $hex): array
    {
        $clean = ltrim(trim($hex), '#');

        if (strlen($clean) === 3) {
            $clean = $clean[0].$clean[0].$clean[1].$clean[1].$clean[2].$clean[2];
        }

        if (preg_match('/^[0-9a-fA-F]{6}$/', $clean) !== 1) {
            throw new InvalidArgumentException("Couleur hexadécimale invalide : {$hex}");
        }

        return [
            (int) hexdec(substr($clean, 0, 2)),
            (int) hexdec(substr($clean, 2, 2)),
            (int) hexdec(substr($clean, 4, 2)),
        ];
    }
}
