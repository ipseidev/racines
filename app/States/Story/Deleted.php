<?php

declare(strict_types=1);

namespace App\States\Story;

/**
 * SUPPRIMÉE — état terminal, aucune transition n'en part.
 */
final class Deleted extends StoryState
{
    public static string $name = 'deleted';
}
