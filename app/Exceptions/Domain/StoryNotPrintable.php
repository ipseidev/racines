<?php

declare(strict_types=1);

namespace App\Exceptions\Domain;

use RuntimeException;

/**
 * Cette histoire ne peut pas entrer dans un livre.
 *
 * Deux cas, et le premier est la promesse entière du produit : le narrateur
 * ne l'a pas validée. L'imprimé est définitif — une histoire imprimée à
 * trente exemplaires ne se retire plus, et aucun bouton du produit ne doit
 * pouvoir en mettre une qu'il n'a pas approuvée.
 *
 * Le second est l'isolation des projets : le livre d'une famille ne contient
 * que ses histoires.
 */
final class StoryNotPrintable extends RuntimeException
{
    public static function notValidated(): self
    {
        return new self('Cette histoire n’a pas été validée par le narrateur : elle ne peut pas entrer dans le livre.');
    }

    public static function otherProject(): self
    {
        return new self('Cette histoire n’appartient pas à ce projet.');
    }
}
