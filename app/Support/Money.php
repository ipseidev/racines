<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\Currency;
use App\Enums\Locale;
use App\Enums\Market;

/**
 * Un prix en centimes, écrit comme on l'écrit dans la langue de la page.
 *
 * Le front a `formatPrice` (`resources/js/lib/intl.ts`) et suit **les mêmes
 * règles, au caractère près** : les métadonnées, le plan de site et les
 * données structurées sont rendus par le serveur, la page par le client, et
 * deux caractères différents pour un même prix seraient une hydratation qui
 * échoue. C'est pour cela qu'on n'appelle pas `NumberFormatter` : l'espace
 * qu'ICU met avant le symbole change d'une version à l'autre.
 *
 * Un prix rond s'écrit sans décimales (« 49 € », jamais « 49,00 € ») : la
 * précision inutile fait paraître le prix plus lourd qu'il n'est. Le
 * séparateur décimal est celui du marché : la virgule en France, en Italie
 * et en Espagne, le point en Suisse. Le symbole de l'euro suit le montant,
 * précédé d'une espace fine insécable pour qu'il ne tombe jamais seul en
 * début de ligne. Le franc suisse précède le montant, comme sur toute
 * étiquette suisse (« CHF 49.– ») — préparé, pas encore facturé (T-238).
 */
final class Money
{
    private const THIN_SPACE = "\u{202f}";

    public static function format(int $cents, ?Locale $locale = null, Currency $currency = Currency::Euro): string
    {
        $locale ??= Locales::current();
        $market = $locale->market();

        $whole = intdiv(abs($cents), 100);
        $rest = abs($cents) % 100;
        $sign = $cents < 0 ? '−' : '';

        $amount = $sign.self::group($whole, $market);

        if ($currency === Currency::SwissFranc) {
            $amount .= $rest === 0 ? '.–' : '.'.self::pad($rest);

            return 'CHF'.self::THIN_SPACE.$amount;
        }

        if ($rest !== 0) {
            $amount .= ($market === Market::Switzerland ? '.' : ',').self::pad($rest);
        }

        return $amount.self::THIN_SPACE.$currency->symbol();
    }

    /** Le prix en euros dans la langue de la requête : l'usage courant. */
    public static function euros(int $cents): string
    {
        return self::format($cents);
    }

    /** Le montant pour une donnée structurée : « 89.00 », point décimal. */
    public static function decimal(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }

    /**
     * Les milliers : espace fine en France et en Espagne, point en Italie,
     * apostrophe en Suisse. Un livre ne coûte pas mille euros, mais une
     * commande de trente exemplaires, si.
     */
    private static function group(int $whole, Market $market): string
    {
        $separator = match ($market) {
            Market::Italy => '.',
            Market::Switzerland => '’',
            default => self::THIN_SPACE,
        };

        return number_format($whole, 0, '', $separator);
    }

    private static function pad(int $rest): string
    {
        return str_pad((string) $rest, 2, '0', STR_PAD_LEFT);
    }
}
