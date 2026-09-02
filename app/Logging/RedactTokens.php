<?php

declare(strict_types=1);

namespace App\Logging;

use App\Enums\TokenType;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

/**
 * Masque les jetons porteurs et les codes à usage unique dans les journaux.
 *
 * Le registre des risques de la roadmap nomme celui-ci : « fuite d'un jeton
 * porteur dans un journal ou un outil d'analytics ». Un lien apparu dans un
 * journal, c'est un lien lisible par quiconque a accès aux journaux — et
 * quiconque détient le lien peut agir à la place du narrateur.
 *
 * Trois formes sont masquées : le segment d'URL (`/r/…`), un jeton posé nu en
 * valeur de contexte sous une clé sensible, et un code à six chiffres.
 */
final class RedactTokens implements ProcessorInterface
{
    public const REPLACEMENT = '[redacted]';

    /** Clés dont la valeur est masquée quelle que soit sa forme. */
    private const SENSITIVE_KEYS = ['token', 'plain', 'code', 'otp', 'token_hash', 'code_hash', 'sg'];

    public function __invoke(LogRecord $record): LogRecord
    {
        return $record
            ->with(message: $this->scrub($record->message))
            ->with(context: $this->scrubArray($record->context))
            ->with(extra: $this->scrubArray($record->extra));
    }

    /**
     * @param  array<array-key, mixed>  $values
     * @return array<array-key, mixed>
     */
    private function scrubArray(array $values): array
    {
        $scrubbed = [];

        foreach ($values as $key => $value) {
            $isSensitiveKey = is_string($key)
                && in_array(mb_strtolower($key), self::SENSITIVE_KEYS, true);

            $scrubbed[$key] = match (true) {
                $isSensitiveKey && (is_string($value) || is_int($value)) => self::REPLACEMENT,
                is_array($value) => $this->scrubArray($value),
                is_string($value) => $this->scrub($value),
                default => $value,
            };
        }

        return $scrubbed;
    }

    private function scrub(string $subject): string
    {
        $prefixes = implode('|', TokenType::urlPrefixes());

        // Segment d'URL : /r/<43 caractères base64url> → /r/[redacted]
        $subject = (string) preg_replace(
            '#/('.$prefixes.')/[A-Za-z0-9_-]{43}#',
            '/$1/'.self::REPLACEMENT,
            $subject,
        );

        // Jeton nu, hors URL : 43 caractères base64url isolés.
        $subject = (string) preg_replace(
            '/(?<![A-Za-z0-9_\-\/])[A-Za-z0-9_-]{43}(?![A-Za-z0-9_\-])/',
            self::REPLACEMENT,
            $subject,
        );

        // Code à usage unique : six chiffres isolés.
        //
        // Sauf en local, où le journal *est* la passerelle d'envoi :
        // `LogSmsSender` y remplace l'opérateur, et masquer le code rendrait
        // impossible de dérouler un parcours narrateur sur sa machine. Partout
        // ailleurs, y compris en test, le code est masqué (décision T-49).
        if (app()->isLocal()) {
            return $subject;
        }

        return (string) preg_replace(
            '/(?<!\d)\d{'.self::codeLength().'}(?!\d)/',
            self::REPLACEMENT,
            $subject,
        );
    }

    private static function codeLength(): int
    {
        $length = config('product.otp.length');

        return is_int($length) ? $length : 6;
    }
}
