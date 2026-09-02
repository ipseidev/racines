<?php

declare(strict_types=1);

namespace App\Exceptions\Domain;

use App\Enums\TokenType;

/**
 * Aucun jeton ne correspond à cette empreinte.
 *
 * Le lien est bien formé — sinon la route l'aurait refusé sans requête — mais
 * il n'existe pas, ou il a été purgé.
 */
final class TokenNotFound extends TokenUnavailable
{
    public static function make(?TokenType $type = null): self
    {
        return new self('not_found', $type);
    }
}
