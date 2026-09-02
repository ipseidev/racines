<?php

declare(strict_types=1);

namespace App\Exceptions\Domain;

use App\Enums\TokenType;

/**
 * Le lien était à usage unique et a déjà servi.
 */
final class TokenUsed extends TokenUnavailable
{
    public static function make(?TokenType $type = null): self
    {
        return new self('used', $type);
    }
}
