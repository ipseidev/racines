<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasTranslatedLabel;

/**
 * Le genre grammatical dans lequel on s'adresse à la narratrice.
 *
 * Il ne sert qu'à accorder : « née » ou « né », « fière » ou « fier ». Nul tant
 * que personne ne l'a dit, et les questions retombent alors sur le point
 * médian, comme avant qu'on le connaisse.
 */
enum GrammaticalGender: string
{
    use HasTranslatedLabel;

    case Feminine = 'feminine';
    case Masculine = 'masculine';
}
