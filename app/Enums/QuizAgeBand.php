<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasTranslatedLabel;

/**
 * La tranche d'âge de la personne qui racontera.
 *
 * Par tranche et non à l'année : l'acheteur ne sait pas toujours, et une
 * question à laquelle on hésite à répondre est une question qu'on abandonne.
 * La tranche suffit à ce qu'on en fait — on ne demande pas la même chose à
 * quelqu'un né en 1935 et à quelqu'un né en 1955.
 */
enum QuizAgeBand: string
{
    use HasTranslatedLabel;

    case Under60 = 'under_60';
    case Sixties = 'sixties';
    case Seventies = 'seventies';
    case Eighties = 'eighties';
    case NinetyPlus = 'ninety_plus';
}
