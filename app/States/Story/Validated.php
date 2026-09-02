<?php

declare(strict_types=1);

namespace App\States\Story;

/**
 * VALIDÉE — acte explicite du narrateur. Jamais un délai écoulé (R-4).
 */
final class Validated extends StoryState
{
    public static string $name = 'validated';
}
