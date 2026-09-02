<?php

declare(strict_types=1);

namespace App\States\Story;

/**
 * PROPOSÉE — la question a été envoyée, rien n'a encore été dit.
 */
final class Proposed extends StoryState
{
    public static string $name = 'proposed';
}
