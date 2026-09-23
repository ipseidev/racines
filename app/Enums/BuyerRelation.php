<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasTranslatedLabel;

/**
 * Qui est l'acheteur pour la narratrice.
 *
 * Décide des questions sur l'acheteur : celles d'un enfant, celles d'un
 * petit-enfant, celles qui valent pour tout lien. Le conjoint n'en reçoit
 * pas — le bloc Couple parle déjà de lui — et qui raconte sa propre histoire
 * non plus.
 */
enum BuyerRelation: string
{
    use HasTranslatedLabel;

    case Child = 'child';
    case Grandchild = 'grandchild';
    case Partner = 'partner';
    case Other = 'other';
    case Myself = 'self';
}
