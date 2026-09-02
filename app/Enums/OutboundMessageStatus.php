<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasTranslatedLabel;

/**
 * Ce qu'un message sortant est devenu.
 *
 * `sent` veut dire « accepté par l'opérateur », pas « lu » ni même « reçu ».
 * Seul `delivered`, rapporté par un webhook, dit que le message est arrivé —
 * et c'est cette distinction qui permet au moteur de complétion de ne pas
 * relancer quelqu'un qui n'a jamais rien reçu.
 */
enum OutboundMessageStatus: string
{
    use HasTranslatedLabel;

    case Queued = 'queued';
    case Sent = 'sent';
    case Delivered = 'delivered';
    case Failed = 'failed';
    case Bounced = 'bounced';
    case Undelivered = 'undelivered';

    public function isFailure(): bool
    {
        return in_array($this, [self::Failed, self::Bounced, self::Undelivered], true);
    }

    public function reached(): bool
    {
        return $this === self::Delivered;
    }
}
