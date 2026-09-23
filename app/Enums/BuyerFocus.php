<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasTranslatedLabel;

/**
 * Des questions sur l'acheteur seul, ou sur toute la fratrie.
 *
 * Une fille qui a deux frères ne reçoit pas un livre qui ne parle que d'elle
 * sans l'avoir demandé : « sur tous », et ce sont les questions du bloc Enfants
 * qui parlent de chacun.
 */
enum BuyerFocus: string
{
    use HasTranslatedLabel;

    case Buyer = 'buyer';
    case Everyone = 'everyone';
}
