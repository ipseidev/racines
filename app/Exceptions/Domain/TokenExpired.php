<?php

declare(strict_types=1);

namespace App\Exceptions\Domain;

use App\Enums\TokenType;

/**
 * Le lien a dépassé sa durée de vie.
 */
final class TokenExpired extends TokenUnavailable
{
    public static function make(?TokenType $type = null): self
    {
        return new self('expired', $type);
    }
}
