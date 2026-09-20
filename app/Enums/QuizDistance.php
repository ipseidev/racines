<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasTranslatedLabel;

/**
 * La distance qui sépare l'acheteur de la personne qui racontera.
 *
 * La réponse ne change aucun réglage du produit : elle change ce qu'on dit à
 * l'écran suivant. On ne parle pas de la même façon à quelqu'un qui déjeune
 * chez sa mère tous les dimanches et à quelqu'un qui l'appelle depuis un
 * autre pays.
 */
enum QuizDistance: string
{
    use HasTranslatedLabel;

    case SameTown = 'same_town';
    case FewHours = 'few_hours';
    case FarAway = 'far_away';
    case Abroad = 'abroad';
}
