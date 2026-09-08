<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Un prix en centimes, écrit à la française, côté serveur.
 *
 * Le front a `formatPrice` ; les métadonnées, le plan de site et les données
 * structurées sont rendus par le serveur et n'y ont pas accès. Une espace
 * insécable avant le symbole, pour qu'il ne tombe jamais seul en début de
 * ligne.
 */
final class Money
{
    public static function euros(int $cents): string
    {
        $whole = intdiv($cents, 100);
        $rest = $cents % 100;

        return $rest === 0
            ? $whole."\u{202f}€"
            : $whole.','.str_pad((string) $rest, 2, '0', STR_PAD_LEFT)."\u{202f}€";
    }

    /** Le montant pour une donnée structurée : « 89.00 », point décimal. */
    public static function decimal(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }
}
