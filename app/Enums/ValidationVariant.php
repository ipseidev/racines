<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasTranslatedLabel;

/**
 * Variante de validation testée par cohorte (flag Pennant du bloc 07).
 *
 * `immediate` = variante A, décision de partage en fin d'enregistrement.
 * `deferred` = variante B, relecture puis validation. La colonne
 * `projects.validation_variant` en garde une copie pour le reporting.
 */
enum ValidationVariant: string
{
    use HasTranslatedLabel;

    case Immediate = 'immediate';
    case Deferred = 'deferred';
}
