<?php

declare(strict_types=1);

namespace App\Exceptions\Domain;

use DomainException;

/**
 * Le code saisi ne correspond pas, ou le défi a déjà été utilisé.
 *
 * Un seul message pour les deux cas : dire « ce code a déjà servi » révélerait
 * qu'il était bon.
 */
final class OtpInvalid extends DomainException
{
    public static function make(): self
    {
        return new self('The one-time code does not match.');
    }
}
