<?php

declare(strict_types=1);

namespace App\Exceptions\Domain;

use DomainException;

/**
 * Un code de réduction ne peut pas servir.
 *
 * Le motif devient le texte affiché à l'acheteur : inconnu, déjà utilisé, ou
 * plus valable. Trois messages distincts, parce que la reprise n'est pas la
 * même : on relit un code inconnu, on ne relit pas un code utilisé.
 */
final class DiscountCodeUnavailable extends DomainException
{
    private function __construct(private readonly string $reason)
    {
        parent::__construct("Discount code unavailable: {$reason}.");
    }

    public static function unknown(): self
    {
        return new self('unknown');
    }

    public static function used(): self
    {
        return new self('used');
    }

    public static function expired(): self
    {
        return new self('expired');
    }

    public function reason(): string
    {
        return $this->reason;
    }
}
