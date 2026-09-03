<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasTranslatedLabel;

/**
 * Pourquoi un narrateur décline l'invitation.
 *
 * Trois motifs, tous facultatifs, et aucun ne demande de se justifier. On les
 * recueille pour comprendre H0 — la première hypothèse du dossier — pas pour
 * répondre à l'objection.
 */
enum RefusalReason: string
{
    use HasTranslatedLabel;

    case NotTheRightTime = 'not_the_right_time';
    case PreferNotTo = 'prefer_not_to';
    case Other = 'other';
}
