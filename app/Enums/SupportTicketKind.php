<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasTranslatedLabel;

/**
 * Ce qui fait ouvrir un ticket au support, sans qu'on l'ait demandé.
 *
 * Un ticket **proactif** : c'est le produit qui signale au support qu'une
 * famille est en difficulté, avant qu'elle n'écrive. Une personne de 82 ans
 * qui n'arrive pas à autoriser son micro n'ouvre pas de ticket — elle
 * abandonne.
 */
enum SupportTicketKind: string
{
    use HasTranslatedLabel;

    case MicDeniedTwice = 'mic_denied_twice';
    case PhoneOptionRequested = 'phone_option_requested';
    case TranscriptionFailed = 'transcription_failed';
}
