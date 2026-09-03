<?php

declare(strict_types=1);

namespace App\Audit;

use App\Enums\TokenType;

/**
 * Masque ce qu'un journal d'audit n'a pas à conserver.
 *
 * Le journal existe pour répondre à « qui a fait quoi, quand ». Il n'existe
 * pas pour dupliquer le carnet d'adresses d'une famille, et un journal lisible
 * par un auditeur externe ne doit pas être un second endroit où fuient les
 * coordonnées.
 *
 * Deux différences avec `RedactTokens`, qui fait un travail voisin sur les
 * journaux applicatifs. D'abord la portée : ici on masque **aussi** les
 * courriels et les numéros, parce qu'une entrée d'audit porte souvent le
 * « avant / après » d'une correction. Ensuite l'irréversibilité : les lignes
 * d'audit ne peuvent pas être modifiées après coup, donc ce qui passe ici y
 * reste pour de bon.
 *
 * Ce qu'on ne masque **pas**, et c'est délibéré : les motifs, les états, les
 * identifiants techniques. Un audit dont on aurait tout retiré ne prouve rien.
 */
final class Redactor
{
    public const REPLACEMENT = '[masqué]';

    /** Clés dont la valeur part quelle que soit sa forme. */
    private const SENSITIVE_KEYS = [
        'token', 'plain', 'code', 'otp', 'token_hash', 'code_hash',
        'password', 'secret', 'email', 'phone', 'phone_e164', 'address',
    ];

    /**
     * @param  array<array-key, mixed>  $payload
     * @return array<array-key, mixed>
     */
    public static function scrub(array $payload): array
    {
        $scrubbed = [];

        foreach ($payload as $key => $value) {
            $sensitive = is_string($key)
                && in_array(mb_strtolower($key), self::SENSITIVE_KEYS, true);

            $scrubbed[$key] = match (true) {
                $sensitive && ! is_array($value) => self::REPLACEMENT,
                is_array($value) => self::scrub($value),
                is_string($value) => self::scrubString($value),
                default => $value,
            };
        }

        return $scrubbed;
    }

    /**
     * Les trois formes qui traînent dans un « avant / après » : un lien, une
     * adresse, un numéro.
     */
    public static function scrubString(string $subject): string
    {
        $prefixes = implode('|', TokenType::urlPrefixes());

        // Un lien complet, puis un jeton nu : dans cet ordre, sinon la seconde
        // expression mange le jeton et la première ne reconnaît plus l'URL.
        $subject = (string) preg_replace(
            '#/('.$prefixes.')/[A-Za-z0-9_-]{43}#',
            '/$1/'.self::REPLACEMENT,
            $subject,
        );

        $subject = (string) preg_replace(
            '/(?<![A-Za-z0-9_\-\/])[A-Za-z0-9_-]{43}(?![A-Za-z0-9_\-])/',
            self::REPLACEMENT,
            $subject,
        );

        $subject = (string) preg_replace(
            '/[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}/',
            self::REPLACEMENT,
            $subject,
        );

        return (string) preg_replace(
            '/\+[1-9]\d{7,14}/',
            self::REPLACEMENT,
            $subject,
        );
    }
}
