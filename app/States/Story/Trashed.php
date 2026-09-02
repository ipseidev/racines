<?php

declare(strict_types=1);

namespace App\States\Story;

/**
 * CORBEILLE — restaurable pendant trente jours (R-4).
 */
final class Trashed extends StoryState
{
    public static string $name = 'trashed';
}
