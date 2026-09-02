<?php

declare(strict_types=1);

namespace App\Exceptions\Domain;

use DomainException;

/**
 * Trop d'essais : le défi est verrouillé, le bon code y compris.
 */
final class OtpLocked extends DomainException
{
    public function __construct(public readonly int $minutes)
    {
        parent::__construct("Too many attempts; locked for {$minutes} minutes.");
    }

    public static function forMinutes(int $minutes): self
    {
        return new self($minutes);
    }
}
