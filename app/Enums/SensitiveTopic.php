<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasTranslatedLabel;

/**
 * Un sujet que la famille peut demander d'éviter.
 *
 * Une étiquette sur la question, jamais une donnée sur la personne : « éviter
 * la maladie » ne dit pas de quoi elle souffre.
 */
enum SensitiveTopic: string
{
    use HasTranslatedLabel;

    case Bereavement = 'bereavement';
    case ChildLoss = 'child_loss';
    case Separation = 'separation';
    case War = 'war';
    case Illness = 'illness';
    case Religion = 'religion';
    /** La question fait penser à la mort de celle qu'on interroge. */
    case EndOfLife = 'end_of_life';
}
