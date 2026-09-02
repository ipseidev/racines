<?php

declare(strict_types=1);

namespace App\States\Story;

/**
 * ENREGISTRÉE — l'audio est confirmé côté stockage. Rien n'est visible des proches.
 */
final class Recorded extends StoryState
{
    public static string $name = 'recorded';
}
