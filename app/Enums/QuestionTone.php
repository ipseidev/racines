<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasTranslatedLabel;

/**
 * Le ton d'une question du corpus.
 *
 * Plus fin que la difficulté, qui mêle l'intimité et l'effort : une question
 * légère fait sourire en répondant, une tendre émeut doucement, une grave
 * touche à la douleur ou au bilan d'une vie.
 */
enum QuestionTone: string
{
    use HasTranslatedLabel;

    case Light = 'light';
    case Tender = 'tender';
    case Grave = 'grave';
}
