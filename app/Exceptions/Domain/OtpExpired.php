<?php

declare(strict_types=1);

namespace App\Exceptions\Domain;

use DomainException;

/**
 * Le code a dépassé ses dix minutes.
 */
final class OtpExpired extends DomainException
{
    public static function make(): self
    {
        return new self('The one-time code has expired.');
    }
}
