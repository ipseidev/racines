<?php

declare(strict_types=1);

namespace App\Analytics;

use App\Exceptions\Domain\AnalyticsPiiLeak;

/**
 * Ce qui n'a pas le droit de partir vers un outil de mesure.
 *
 * Trois formes, et chacune a coûté quelque chose à quelqu'un ailleurs :
 *
 *  - **Une adresse de courriel.** Elle identifie directement.
 *  - **Un numéro au format international.** Idem, et ce produit en manipule
 *    à chaque envoi.
 *  - **Un jeton porteur.** Le plus grave des trois : quarante-trois
 *    caractères qui **ouvrent** la page de quelqu'un. Un jeton dans une URL
 *    rapportée à un outil d'analytique donne à cet outil — et à quiconque lit
 *    ses journaux — l'accès aux récits d'une famille (doc 04 §12).
 *
 * La garde inspecte **les clés autant que les valeurs**, et descend dans les
 * tableaux : une fuite se cache dans une propriété profonde, construite
 * ailleurs et passée sans être relue.
 *
 * Elle **lève**. Voir `AnalyticsPiiLeak` pour la raison.
 */
final class PiiGuard
{
    /** Le motif d'un jeton porteur : quarante-trois caractères base64url. */
    private const TOKEN = '/(?<![A-Za-z0-9_-])[A-Za-z0-9_-]{43}(?![A-Za-z0-9_-])/';

    private const EMAIL = '/[\w.+-]+@[\w-]+\.[\w.-]+/';

    /** E.164 : un plus, un indicatif, huit à quatorze chiffres. */
    private const PHONE = '/\+\d{9,15}\b/';

    /**
     * @param  array<mixed>  $properties
     *
     * @throws AnalyticsPiiLeak
     */
    public static function assertClean(array $properties, string $prefix = ''): void
    {
        foreach ($properties as $key => $value) {
            $path = $prefix === '' ? (string) $key : $prefix.'.'.$key;

            self::assertScalarClean((string) $key, $path);

            if (is_array($value)) {
                self::assertClean($value, $path);

                continue;
            }

            if (is_string($value)) {
                self::assertScalarClean($value, $path);
            }
        }
    }

    private static function assertScalarClean(string $value, string $path): void
    {
        if (preg_match(self::EMAIL, $value) === 1) {
            throw AnalyticsPiiLeak::property($path, 'une adresse de courriel');
        }

        if (preg_match(self::PHONE, $value) === 1) {
            throw AnalyticsPiiLeak::property($path, 'un numéro de téléphone');
        }

        if (preg_match(self::TOKEN, $value) === 1) {
            throw AnalyticsPiiLeak::property($path, 'ce qui ressemble à un jeton d’accès');
        }
    }
}
