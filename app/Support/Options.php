<?php

declare(strict_types=1);

namespace App\Support;

use BackedEnum;

/**
 * Les choix d'une énumération, prêts pour un `<select>` ou une liste de boutons.
 *
 * Pourquoi ici et pas dans le catalogue envoyé au front : `lang/fr/enums.php`
 * pèse neuf kilo-octets, et les pages narrateur s'ouvrent en 4G sur de vieux
 * téléphones (PRD US-01). Pousser toutes les énumérations du domaine sur
 * chaque page pour en afficher trois serait payer la traduction de tout le
 * domaine à chaque écran. Une page emporte donc **les libellés dont elle a
 * besoin**, déjà traduits.
 *
 * Deuxième raison, moins visible : `HasTranslatedLabel::label()` rend une
 * **clé**, pas du texte. Un contrôleur qui la passe telle quelle au front
 * envoie « enums.channel.sms » à quelqu'un dont le catalogue ne contient pas
 * `enums`. Passer par ici rend la faute impossible.
 */
final class Options
{
    /**
     * @param  class-string<BackedEnum>  $enum
     * @return list<array{value: string, label: string}>
     */
    public static function of(string $enum): array
    {
        return array_map(
            static fn (BackedEnum $case): array => [
                'value' => (string) $case->value,
                'label' => self::label($case),
            ],
            $enum::cases(),
        );
    }

    /**
     * Le libellé traduit d'un cas.
     *
     * Tolérant à une énumération qui n'emploie pas `HasTranslatedLabel` : elle
     * rend alors sa valeur, ce qui est laid mais lisible, plutôt qu'une erreur
     * au milieu d'une page.
     */
    public static function label(BackedEnum $case): string
    {
        if (! method_exists($case, 'label')) {
            return (string) $case->value;
        }

        return __($case->label());
    }
}
