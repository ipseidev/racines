<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Un numéro de téléphone tel qu'on le tape, ramené au format international.
 *
 * Personne n'écrit « +33612345678 » : on écrit « 06 12 34 56 78 », parfois
 * « 06.12.34.56.78 », parfois « 0033 6… ». Demander le format international
 * à l'acheteur, c'est lui faire porter une contrainte technique (T-136). On
 * accepte ce qu'il tape, et on normalise avant de valider.
 *
 * Ce qui ne ressemble à rien est rendu tel quel, nettoyé : la règle de
 * validation E.164 fera son travail et le message d'erreur restera vrai.
 */
final class Phone
{
    /** Indicatifs par pays : la France d'abord, le reste viendra avec la demande. */
    private const COUNTRY_CODES = [
        'FR' => '33',
        'BE' => '32',
        'CH' => '41',
    ];

    public static function e164(?string $raw, string $country = 'FR'): ?string
    {
        if ($raw === null) {
            return null;
        }

        $trimmed = trim($raw);

        if ($trimmed === '') {
            return null;
        }

        $international = str_starts_with($trimmed, '+');
        $digits = preg_replace('/\D+/', '', $trimmed) ?? '';

        if ($digits === '') {
            return $trimmed;
        }

        if ($international) {
            return '+'.$digits;
        }

        if (str_starts_with($digits, '00')) {
            return '+'.substr($digits, 2);
        }

        $code = self::COUNTRY_CODES[$country] ?? null;

        if ($code === null) {
            return $trimmed;
        }

        // « 06 12 34 56 78 » : le zéro de tête tombe, l'indicatif le remplace.
        if (strlen($digits) === 10 && str_starts_with($digits, '0')) {
            return '+'.$code.substr($digits, 1);
        }

        // « 6 12 34 56 78 » : neuf chiffres sans le zéro, ça arrive.
        if (strlen($digits) === 9 && ! str_starts_with($digits, '0')) {
            return '+'.$code.$digits;
        }

        return $trimmed;
    }
}
