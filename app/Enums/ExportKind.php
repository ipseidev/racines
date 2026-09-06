<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasTranslatedLabel;

/**
 * Ce que contient un export.
 *
 * Trois genres, et ce qui les distingue n'est pas le format — c'est **à qui
 * ils répondent**. `full` répond à « je veux mes affaires » ;
 * `offline_pack` répond à « et si vous disparaissiez ? » ; `gdpr_access`
 * répond à une demande de droit d'accès, qui exige en plus les consentements
 * et le journal de ce qui a été fait de ces données.
 */
enum ExportKind: string
{
    use HasTranslatedLabel;

    /** Tout ce que la famille a confié, lisible sans le service. */
    case Full = 'full';

    /**
     * Les audios, plus un lecteur HTML autonome.
     *
     * C'est la contrepartie de la durée d'hébergement annoncée (D-8) : le
     * jour où les QR du livre s'éteindront, ce pack continuera de jouer les
     * voix depuis une clé USB, sans nous.
     */
    case OfflinePack = 'offline_pack';

    /** Le droit d'accès du RGPD : tout, plus les consentements et le journal. */
    case GdprAccess = 'gdpr_access';
}
