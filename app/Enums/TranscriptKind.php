<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasTranslatedLabel;

/**
 * Nature d'un texte d'histoire (glossaire §2).
 *
 * Les trois coexistent, et c'est le point : le verbatim est la parole, le
 * Fluide est ce qui va dans le livre, l'édité est ce que la famille a corrigé.
 * Aucun ne remplace l'autre.
 */
enum TranscriptKind: string
{
    use HasTranslatedLabel;

    case Verbatim = 'verbatim';
    case Fluide = 'fluide';
    case Edited = 'edited';
}
