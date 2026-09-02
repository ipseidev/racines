<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\TokenType;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * L'autorisation d'un acte sensible, portée par un cookie.
 *
 * Le jeton `sensitive_grant` ne voyage jamais dans une URL : il vivrait dans
 * l'historique du navigateur, dans les journaux et dans l'en-tête `Referer`.
 * Il voyage donc dans un cookie `HttpOnly`, `SameSite=Strict`, dont la durée
 * est celle du jeton — quinze minutes.
 */
final class SensitiveGrant
{
    public const COOKIE = 'sg';

    public static function cookie(string $plain): Cookie
    {
        return cookie()->make(
            name: self::COOKIE,
            value: $plain,
            minutes: self::minutes(),
            secure: ! app()->isLocal(),
            httpOnly: true,
            sameSite: 'strict',
        );
    }

    public static function forget(): Cookie
    {
        return cookie()->forget(self::COOKIE);
    }

    public static function minutes(): int
    {
        $minutes = config('product.tokens.sensitive_grant_minutes');

        return is_int($minutes) ? $minutes : 15;
    }

    public static function type(): TokenType
    {
        return TokenType::SensitiveGrant;
    }
}
