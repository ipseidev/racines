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

    /**
     * Au-delà d'un segment, sept octets par morceau partent dans l'en-tête de
     * réassemblage : la place utile tombe de 160 à 153, et de 70 à 67.
     */
    public const GSM7_CONCATENATED_SEGMENT = 153;

    public const UCS2_CONCATENATED_SEGMENT = 67;

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
     * Le nombre de segments facturés — donc le coût du canal, et le nombre de
     * morceaux qu'un téléphone ancien peut afficher séparément.
     */
    public static function segments(string $body): int
    {
        $length = self::length($body);

        if ($length === 0) {
            return 0;
        }

        if ($length <= self::segmentLimit($body)) {
            return 1;
        }

        $perSegment = self::isGsm7($body)
            ? self::GSM7_CONCATENATED_SEGMENT
            : self::UCS2_CONCATENATED_SEGMENT;

        return (int) ceil($length / $perSegment);
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
