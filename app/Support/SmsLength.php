<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Longueur d'un SMS, en segments.
 *
 * Un SMS dépasse un segment à 160 caractères en GSM-7, mais à **70**
 * seulement si un seul caractère sort de cet alphabet — et « votre » avec une
 * apostrophe typographique suffit. Un message découpé arrive parfois dans le
 * désordre, et certains téléphones anciens n'affichent que le premier morceau.
 * D'où ce calcul, et non un simple `strlen`.
 */
final class SmsLength
{
    public const GSM7_SINGLE_SEGMENT = 160;

    public const UCS2_SINGLE_SEGMENT = 70;

    /** Caractères GSM-7 comptant double (norme 3GPP 23.038). */
    private const GSM7_EXTENDED = ['^', '{', '}', '\\', '[', ']', '~', '|', '€'];

    /** Alphabet GSM-7 (norme 3GPP 23.038), en deux morceaux pour la lisibilité. */
    private const GSM7_BASE = "@£\$¥èéùìòÇ\nØø\rÅåΔ_ΦΓΛΩΠΨΣΘΞÆæßÉ !\"#¤%&'()*+,-./0123456789:;<=>?¡ABCDEFGHIJKLMNOPQRSTUVWXYZÄÖÑÜ§¿abcdefghijklmnopqrstuvwxyzäöñüà";

    public static function isGsm7(string $body): bool
    {
        foreach (mb_str_split($body) as $character) {
            if (! in_array($character, self::GSM7_EXTENDED, true)
                && mb_strpos(self::GSM7_BASE, $character) === false) {
                return false;
            }
        }

        return true;
    }

    public static function segmentLimit(string $body): int
    {
        return self::isGsm7($body) ? self::GSM7_SINGLE_SEGMENT : self::UCS2_SINGLE_SEGMENT;
    }

    public static function length(string $body): int
    {
        if (! self::isGsm7($body)) {
            return mb_strlen($body);
        }

        $length = 0;

        foreach (mb_str_split($body) as $character) {
            $length += in_array($character, self::GSM7_EXTENDED, true) ? 2 : 1;
        }

        return $length;
    }

    public static function exceedsSingleSegment(string $body): bool
    {
        return self::length($body) > self::segmentLimit($body);
    }

    /**
     * Raccourcit un prénom sans le rendre méconnaissable : « Marie-Christine »
     * devient « Marie », pas « Mar. ».
     */
    public static function shorten(string $firstName, int $maximum = 12): string
    {
        $firstName = trim($firstName);

        if (mb_strlen($firstName) <= $maximum) {
            return $firstName;
        }

        $head = trim((string) (preg_split('/[\s\-]/u', $firstName)[0] ?? $firstName));

        return mb_strlen($head) <= $maximum ? $head : mb_substr($head, 0, $maximum);
    }
}
