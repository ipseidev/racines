<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasTranslatedLabel;

/**
 * Étape d'un envoi en plusieurs parts.
 *
 * `completed` ne veut pas dire « confirmé » : c'est `confirmed_at`, posé après
 * un `HeadObject` réussi, qui autorise à annoncer l'enregistrement au
 * narrateur (doc 04 §11).
 */
enum UploadStatus: string
{
    use HasTranslatedLabel;

    case Initiated = 'initiated';
    case Uploading = 'uploading';
    case Completed = 'completed';
    case Failed = 'failed';
    case Aborted = 'aborted';
}
