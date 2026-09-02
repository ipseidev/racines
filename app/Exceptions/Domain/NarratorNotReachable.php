<?php

declare(strict_types=1);

namespace App\Exceptions\Domain;

use DomainException;

/**
 * Un narrateur doit être joignable par SMS ou par courriel : sans cela, aucun
 * lien d'enregistrement ne peut lui parvenir (R-9).
 */
final class NarratorNotReachable extends DomainException
{
    public static function make(): self
    {
        return new self('A narrator needs either a phone number or an email address.');
    }
}
