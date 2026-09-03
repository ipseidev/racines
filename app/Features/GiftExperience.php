<?php

declare(strict_types=1);

namespace App\Features;

use App\Models\Project;

/**
 * La forme que prend le cadeau.
 *
 * `ecard` par défaut : c'est la seule des trois qui ne dépend de rien —
 * ni d'un imprimeur, ni d'un enregistrement réussi de l'acheteur. Les deux
 * autres sont des variantes à mesurer, pas des promesses par défaut.
 */
final class GiftExperience
{
    public string $name = 'gift-experience';

    public const ECARD = 'ecard';

    public const PRINTED_CARD = 'printed-card';

    public const AUDIO_MESSAGE = 'audio-message';

    public function resolve(?Project $project = null): string
    {
        return self::ECARD;
    }

    /**
     * @return list<string>
     */
    public static function variants(): array
    {
        return [self::ECARD, self::PRINTED_CARD, self::AUDIO_MESSAGE];
    }
}
