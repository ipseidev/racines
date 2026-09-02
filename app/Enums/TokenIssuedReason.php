<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasTranslatedLabel;

/**
 * Pourquoi ce jeton existe.
 *
 * Utile au support : un narrateur qui a reçu trois liens pour la même
 * histoire doit pouvoir s'expliquer par des raisons lisibles, et non par une
 * suite d'émissions anonymes.
 */
enum TokenIssuedReason: string
{
    use HasTranslatedLabel;

    case Initial = 'initial';
    case ReissueSupport = 'reissue_support';
    case ResendOtherChannel = 'resend_other_channel';
    case Rotation = 'rotation';
}
