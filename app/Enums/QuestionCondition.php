<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasTranslatedLabel;

/**
 * Ce qu'une question suppose de la vie de la narratrice.
 *
 * Quand la condition est fausse, la question ne part pas : on ne demande pas
 * « qu'avez-vous ressenti en tenant votre premier enfant » à une femme qui
 * n'en a pas eu.
 */
enum QuestionCondition: string
{
    use HasTranslatedLabel;

    /** A vécu une vie de couple : en couple, veuve ou séparée. */
    case Partner = 'partner';
    case Children = 'children';
    case Grandchildren = 'grandchildren';
    /** A exercé un métier. */
    case Career = 'career';
    /** A grandi avec ses parents, ou les a connus. */
    case KnewParents = 'knew_parents';
    /** A des frères ou des sœurs. */
    case Siblings = 'siblings';
}
