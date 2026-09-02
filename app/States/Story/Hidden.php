<?php

declare(strict_types=1);

namespace App\States\Story;

/**
 * MASQUÉE — retirée de la vue des proches, sans rien supprimer. Réversible.
 */
final class Hidden extends StoryState
{
    public static string $name = 'hidden';
}
