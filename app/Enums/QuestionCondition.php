<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasTranslatedLabel;

/**
 * Ce qu'une question suppose de la vie de la narratrice.
 *
 * Quand la condition est fausse, la question ne part pas : on ne demande pas
 * « qu'avez-vous ressenti en tenant votre premier enfant » à une femme qui
 * n'en a pas eu.
 */
enum QuestionCondition: string
{
    use HasTranslatedLabel;

    /** A vécu une vie de couple : en couple, veuve ou séparée. */
    case Partner = 'partner';
    case Children = 'children';
    case Grandchildren = 'grandchildren';
    /** A exercé un métier. */
    case Career = 'career';
    /** A grandi avec ses parents, ou les a connus. */
    case KnewParents = 'knew_parents';
    /** A des frères ou des sœurs. */
    case Siblings = 'siblings';
    /** A grandi dans un autre pays, ou une région lointaine, que celle où il vit. */
    case Migration = 'migration';
    /** A grandi à la campagne, à la ferme ou dans un village. */
    case Rural = 'rural';
    /**
     * La question parle de l'acheteur, qui l'a demandé : le tunnel a dit « sur
     * moi », et ce n'est pas un achat pour soi. Elle nomme l'acheteur
     * (`{{prénom}}`) et ne part pas sans son prénom.
     */
    case AboutBuyer = 'about_buyer';
    /** L'acheteur est l'enfant de la narratrice. */
    case BuyerIsChild = 'buyer_is_child';
    /** L'acheteur est son petit-enfant. */
    case BuyerIsGrandchild = 'buyer_is_grandchild';

    /**
     * Les conditions de vie : ce qu'on demande à la famille, à vrai ou faux.
     * Les trois autres parlent de l'acheteur et se déduisent de son lien.
     *
     * @return list<self>
     */
    public static function lifeFacts(): array
    {
        return [
            self::Partner, self::Children, self::Grandchildren, self::Career,
            self::KnewParents, self::Siblings, self::Migration, self::Rural,
        ];
    }
}
