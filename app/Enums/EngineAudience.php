<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * À qui une règle du moteur s'adresse.
 *
 * Ce n'est pas une étiquette d'affichage : chaque public a **sa** limite, et
 * elles ne se ressemblent pas.
 *
 *  - Le **narrateur** ne reçoit jamais deux messages du moteur le même jour
 *    (règle §9 du bloc 09). C'est la personne qu'on risque de faire fuir.
 *  - L'**Initiateur·rice** ne reçoit pas plus de quatre sollicitations par
 *    mois (R-7). C'est la personne qu'on risque d'épuiser.
 *  - Les **proches** n'ont pas de plafond global : un nudge par histoire
 *    suffit à les borner, et ils sont plusieurs à se partager la charge.
 *  - Le **support** n'est pas une personne à ménager mais une équipe à
 *    alerter : aucun plafond.
 */
enum EngineAudience: string
{
    case Narrator = 'narrator';
    case Initiator = 'initiator';
    case Family = 'family';
    /**
     * Le support, quand le produit lève la main à la place d'une famille.
     * Sans plafond : personne n'abandonne parce qu'un ticket de plus a été
     * ouvert, et un ticket manquant, lui, coûte une famille.
     */
    case Support = 'support';
}
