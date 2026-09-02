<?php

declare(strict_types=1);

namespace App\Exceptions\Domain;

use DomainException;

/**
 * Un consentement recueilli par téléphone est toujours attribué à un opérateur
 * nommé : c'est ce qui rend l'accord oral vérifiable (D-9, doc 04 §2).
 */
final class ConsentOperatorRequired extends DomainException
{
    public static function make(): self
    {
        return new self('A consent recorded by phone must name the operator who took it.');
    }
}
