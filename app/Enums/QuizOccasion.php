<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasTranslatedLabel;

/**
 * L'occasion qui fait offrir, s'il y en a une.
 *
 * Elle décide de la date d'envoi de l'invitation, et rien d'autre. Pas de
 * compte à rebours ni de « plus que trois jours » : l'échéance réelle d'un
 * cadeau se suffit, et une urgence fabriquée serait la seule chose fausse de
 * tout le parcours.
 */
enum QuizOccasion: string
{
    use HasTranslatedLabel;

    case Birthday = 'birthday';
    case Christmas = 'christmas';
    case ParentsDay = 'parents_day';
    case NoOccasion = 'no_occasion';
}
