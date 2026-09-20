<?php

declare(strict_types=1);

namespace App\Support;

/**
 * « de Marie », « d'Odette », « di Marco », « de Ana ».
 *
 * Le jumeau serveur de `ofName()` dans `resources/js/lib/intl.ts`, et il en
 * est le jumeau au sens strict : les deux doivent rendre **les mêmes octets**
 * pour le même prénom, comme `Money` et `useFormat()`. Le titre d'un livre se
 * choisit à l'écran et s'imprime des mois plus tard ; si les deux divisaient
 * l'apostrophe autrement, la couverture reçue ne serait pas celle qui avait
 * été montrée. Un test les compare sur le même corpus de prénoms.
 *
 * Le français élide devant une voyelle ou un h muet. L'italien le fait aussi
 * à l'oral soigné, mais l'usage écrit courant garde « di Anna » : on ne
 * l'élide pas. L'espagnol n'élide jamais.
 *
 * Le h aspiré (Hans, Hugues) n'est pas distingué : il est rare, et l'erreur
 * inverse — « de Odette » — se lirait sur chaque couverture.
 */
final class Names
{
    private const ELIDABLE = '/^[aeiouyhàâäéèêëîïôöùûü]/iu';

    public static function of(string $name, ?string $language = null): string
    {
        $language ??= Locales::current()->language();
        $trimmed = trim($name);

        return match ($language) {
            'it' => 'di '.$trimmed,
            'es' => 'de '.$trimmed,
            default => preg_match(self::ELIDABLE, $trimmed) === 1
                ? 'd’'.$trimmed
                : 'de '.$trimmed,
        };
    }
}
