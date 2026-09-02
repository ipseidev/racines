<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasTranslatedLabel;

enum SupportTicketStatus: string
{
    use HasTranslatedLabel;

    case Open = 'open';
    case Closed = 'closed';
}
