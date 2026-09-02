<?php

declare(strict_types=1);

namespace App\Exceptions\Domain;

use DomainException;

/**
 * Impossible d'envoyer le code : le sujet n'a pas de coordonnée pour ce canal.
 */
final class OtpNotDeliverable extends DomainException
{
    public static function on(string $channel): self
    {
        return new self("The subject has no contact details for channel [{$channel}].");
    }
}
