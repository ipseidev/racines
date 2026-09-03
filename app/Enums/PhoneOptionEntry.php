<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasTranslatedLabel;

/**
 * Par où l'option téléphone est arrivée.
 *
 * La distinction compte pour la mesure : une option achetée au tunnel
 * (`checkout`) dit qu'on peut la vendre ; une option demandée en rattrapage
 * (`rescue`, sur alerte du moteur au bout de trois semaines de silence) dit
 * qu'elle sauve des projets. Ce ne sont pas les mêmes chiffres, et ils ne
 * mènent pas aux mêmes décisions.
 */
enum PhoneOptionEntry: string
{
    use HasTranslatedLabel;

    case Checkout = 'checkout';
    case Rescue = 'rescue';
}
