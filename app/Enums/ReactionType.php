<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasTranslatedLabel;

/**
 * Ce qu'un proche peut dire en un tap.
 *
 * Deux formes, et pas de pouce baissé : le produit ne propose aucune façon de
 * désapprouver le souvenir de quelqu'un. Un mot court reste possible à côté,
 * pour dire ce qu'un pictogramme ne dit pas.
 */
enum ReactionType: string
{
    use HasTranslatedLabel;

    case Heart = 'heart';
    case Thanks = 'thanks';
}
