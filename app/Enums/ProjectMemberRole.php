<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasTranslatedLabel;

/**
 * Rôle d'un utilisateur sur un projet (glossaire §1).
 */
enum ProjectMemberRole: string
{
    use HasTranslatedLabel;

    case Initiator = 'initiator';
    case Editor = 'editor';
}
