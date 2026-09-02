<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasTranslatedLabel;

/**
 * Créneau d'envoi de la question (décision T-28).
 */
enum PromptSlot: string
{
    use HasTranslatedLabel;

    case Morning = 'morning';
    case Afternoon = 'afternoon';
    case Evening = 'evening';

    /**
     * Heure locale du créneau, lue dans `config('product.schedule.slots')`.
     */
    public function hour(): int
    {
        $hours = config('product.schedule.slots');

        return is_array($hours) && is_int($hours[$this->value] ?? null)
            ? $hours[$this->value]
            : 9;
    }
}
