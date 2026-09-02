<?php

declare(strict_types=1);

namespace App\States\Story;

/**
 * À RELIRE — le narrateur est invité à relire avant de valider (variante B).
 */
final class ToReview extends StoryState
{
    public static string $name = 'to_review';
}
