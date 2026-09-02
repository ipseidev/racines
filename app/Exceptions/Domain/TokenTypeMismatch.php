<?php

declare(strict_types=1);

namespace App\Exceptions\Domain;

use App\Enums\TokenType;

/**
 * Le jeton existe mais n'a pas le périmètre attendu par cette route.
 *
 * Un lien d'écoute présenté à la page d'enregistrement, par exemple : refusé,
 * car un jeton vaut pour un type et un seul.
 */
final class TokenTypeMismatch extends TokenUnavailable
{
    public static function make(?TokenType $type = null): self
    {
        return new self('type_mismatch', $type);
    }
}
