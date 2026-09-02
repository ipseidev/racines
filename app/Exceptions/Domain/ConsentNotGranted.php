<?php

declare(strict_types=1);

namespace App\Exceptions\Domain;

use DomainException;

/**
 * On ne révoque que ce qui a été accordé.
 */
final class ConsentNotGranted extends DomainException
{
    public static function forKind(string $kind): self
    {
        return new self("No granted consent of kind [{$kind}] to revoke.");
    }
}
