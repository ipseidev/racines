<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasTranslatedLabel;

/**
 * À quel point la personne qui racontera est à l'aise avec un téléphone.
 *
 * Déclaré par l'acheteur quand il offre à un proche (T-136). Ce n'est pas un
 * sondage : la réponse change ce qu'on propose. Les deux derniers cas
 * recommandent l'option téléphone, et les pages de la narratrice pourront
 * doser leur aide d'après lui.
 */
enum TechComfort: string
{
    use HasTranslatedLabel;

    case Daily = 'daily';
    case Sometimes = 'sometimes';
    case Rarely = 'rarely';
    case NoSmartphone = 'no_smartphone';

    /** L'option téléphone lui rendrait la vie plus simple. */
    public function suggestsPhoneOption(): bool
    {
        return $this === self::Rarely || $this === self::NoSmartphone;
    }
}
