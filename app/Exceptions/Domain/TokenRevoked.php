<?php

declare(strict_types=1);

namespace App\Exceptions\Domain;

use App\Enums\TokenType;

/**
 * Le lien a été révoqué : validation de l'histoire, ré-émission, ou retrait
 * demandé par le narrateur.
 */
final class TokenRevoked extends TokenUnavailable
{
    public static function make(?TokenType $type = null): self
    {
        return new self('revoked', $type);
    }
}
