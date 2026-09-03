<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasTranslatedLabel;

/**
 * La forme que prend le livrable, selon la matière recueillie.
 *
 * Trois formats, et le troisième est celui qui compte. « Chapitre fondateur »
 * n'est pas un lot de consolation : c'est un objet relié, court, qui existe.
 * Une famille qui a raconté trois histoires a raconté trois histoires de plus
 * que si on ne lui avait rien demandé, et lui rendre un export ZIP serait
 * transformer une réussite modeste en échec (PRD §10).
 */
enum BookFormat: string
{
    use HasTranslatedLabel;

    /** Matière riche : R-6 atteint. */
    case Book = 'book';

    /** Matière intermédiaire : 24 à 60 pages, même qualité de fabrication. */
    case Booklet = 'booklet';

    /** Matière faible : un relié court, plus l'export et le pack audio. */
    case FoundingChapter = 'founding_chapter';
}
