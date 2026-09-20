<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasTranslatedLabel;

/**
 * Comment la personne raconte, vue par celui qui l'écoute depuis toujours.
 *
 * `NothingToTell` est la réponse la plus fréquente et la plus fausse : elle
 * ouvre l'écran qui explique qu'une question précise obtient ce qu'une
 * question vague n'obtient jamais. Les deux dernières valeurs disent aussi
 * quelque chose au moteur de complétion, qui n'attend pas la même chose d'un
 * narrateur intarissable et d'un narrateur qu'il faut relancer.
 */
enum QuizStorytellerStyle: string
{
    use HasTranslatedLabel;

    case Endless = 'endless';
    case NeedsNudge = 'needs_nudge';
    case NothingToTell = 'nothing_to_tell';
    case NeverTried = 'never_tried';
}
