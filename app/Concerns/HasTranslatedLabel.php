<?php

declare(strict_types=1);

namespace App\Concerns;

use Illuminate\Support\Str;

/**
 * Libellé traduisible d'une énumération de domaine.
 *
 * `label()` ne renvoie jamais du texte : il renvoie une clé de `lang/fr`, pour
 * que l'interface reste entièrement traduisible et qu'aucune chaîne visible ne
 * vive dans le code (convention §10).
 *
 * @phpstan-require-implements \BackedEnum
 */
trait HasTranslatedLabel
{
    public function label(): string
    {
        return 'enums.'.Str::snake(class_basename(self::class)).'.'.$this->value;
    }
}
