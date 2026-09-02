<?php

declare(strict_types=1);

namespace App\Exceptions\Domain;

use DomainException;

/**
 * Trop de codes demandés dans l'heure pour ce sujet.
 *
 * Protège le narrateur d'un harcèlement par SMS autant que le produit d'une
 * facture d'opérateur.
 */
final class OtpThrottled extends DomainException
{
    public static function make(): self
    {
        return new self('Too many one-time codes requested for this subject.');
    }
}
