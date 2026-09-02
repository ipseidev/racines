<?php

declare(strict_types=1);

namespace App\States\Story;

/**
 * ARCHIVÉE — sortie du fil courant, conservée.
 */
final class Archived extends StoryState
{
    public static string $name = 'archived';
}
