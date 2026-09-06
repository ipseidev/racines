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
    /*
     * Bloc 10 : une rétractation demandée, et l'offre de remboursement quand
     * le narrateur décline l'invitation. Toutes deux se traitent à la main —
     * il y a une personne au bout, et un virement automatique ne dit pas
     * qu'on a compris.
     */
    case WithdrawalRequested = 'withdrawal_requested';
    case RefundOffer = 'refund_offer';
    /*
     * Bloc 13 : l'impression, entièrement manuelle au pilote (§9 du bloc).
     * `print_order` porte le lien du bon à tirer approuvé ; `print_defect`
     * ouvre une réimpression gratuite, que le dossier promet sans condition
     * (doc 04 §10) — la juger au cas par cas coûterait plus en discussion
     * qu'un exemplaire n'en coûte à réimprimer.
     */
    case PrintOrder = 'print_order';
    case PrintDefect = 'print_defect';
    /*
     * Bloc 14 : une demande d'effacement. Elle ouvre un ticket et **n'exécute
     * rien** : un humain confirme, parce que personne ne récupère ce qui part
     * par erreur à deux heures du matin.
     */
    case ErasureRequested = 'erasure_requested';
}
