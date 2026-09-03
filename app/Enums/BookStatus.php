<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasTranslatedLabel;

/**
 * Où en est le livre.
 *
 * `proofing` et `approved` sont deux états distincts, et la distinction est
 * tout l'objet du bon à tirer : entre le PDF prêt et l'accord de la famille,
 * il y a un acte — et cet acte engage, parce que l'imprimé est définitif.
 *
 * `reprint` existe parce qu'un défaut d'impression ou de transport donne droit
 * à une réimpression gratuite (doc 04 §10). Ce n'est pas un retour à
 * `ordered` : la commande d'origine a bien été passée, et l'historique doit le
 * dire.
 */
enum BookStatus: string
{
    use HasTranslatedLabel;

    case Draft = 'draft';
    case Proofing = 'proofing';
    case Approved = 'approved';
    case Ordered = 'ordered';
    case Printed = 'printed';
    case Delivered = 'delivered';
    case Reprint = 'reprint';

    /** Après l'accord, la sélection des chapitres est verrouillée. */
    public function isLocked(): bool
    {
        return $this !== self::Draft && $this !== self::Proofing;
    }
}
