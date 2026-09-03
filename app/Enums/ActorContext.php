<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasTranslatedLabel;

/**
 * D'où vient une action inscrite au journal d'audit.
 *
 * Le contexte compte autant que l'acteur : « le support a masqué une
 * histoire » et « une commande planifiée a masqué une histoire » sont deux
 * faits différents, et un journal qui les confondrait ne servirait à rien le
 * jour où il faut répondre à une famille.
 */
enum ActorContext: string
{
    use HasTranslatedLabel;

    /** Une requête HTTP d'un espace public ou à jeton. */
    case Web = 'web';

    /** Le panneau d'administration. */
    case Filament = 'filament';

    /** Une commande Artisan lancée à la main. */
    case Cli = 'cli';

    /** Un opérateur au téléphone, qui saisit pour quelqu'un d'autre (D-9). */
    case PhoneOperator = 'phone_operator';

    /** Le planificateur, une file, un webhook : personne. */
    case System = 'system';
}
