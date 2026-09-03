<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasTranslatedLabel;

/**
 * Ce que le narrateur veut qu'il advienne de ses histoires après sa mort.
 *
 * Trois choix, et le troisième est le plus important : quelqu'un a le droit
 * de vouloir que ses récits disparaissent avec lui. Ses directives **priment**
 * sur la demande de ses proches (doc 04 §6) — c'est le seul endroit du produit
 * où l'on tranche contre la famille, et c'est délibéré.
 */
enum PostMortemWish: string
{
    use HasTranslatedLabel;

    case TransferToFamily = 'transfer_to_family';
    case Freeze = 'freeze';
    case Delete = 'delete';
}
