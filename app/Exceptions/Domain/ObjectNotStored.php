<?php

declare(strict_types=1);

namespace App\Exceptions\Domain;

use DomainException;

/**
 * Le stockage ne détient pas l'objet attendu.
 *
 * Lever plutôt que retourner une taille nulle : c'est cette exception qui
 * empêche d'annoncer un enregistrement conservé alors qu'il ne l'est pas.
 */
final class ObjectNotStored extends DomainException
{
    public static function at(string $key, string $disk): self
    {
        return new self("No object stored at [{$key}] on disk [{$disk}].");
    }
}
