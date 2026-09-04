<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Un pourcentage, écrit comme on l'écrit en France : « 10 % », avec une
 * espace fine insécable avant le signe. Un courriel qui coupe « 10 » et « % »
 * sur deux lignes fait douter de la réduction.
 */
final class Percent
{
    public static function format(int $percent): string
    {
        return $percent."\u{202F}%";
    }
}
