<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasTranslatedLabel;

/**
 * À qui l'acheteur pense, au tout premier écran du tunnel de découverte.
 *
 * Le premier écran ne demande ni prénom ni adresse : il demande de qui il
 * s'agit, parce que c'est le geste le moins coûteux qui soit, et parce que
 * tous les écrans suivants s'écrivent ensuite avec le bon sujet — « votre
 * mère », « vos grands-parents » — plutôt qu'avec un « cette personne » qui
 * ne ressemble à personne.
 *
 * `Myself` n'est pas un cas comme les autres : il quitte le quiz. Les douze
 * écrans qui suivent sont écrits pour quelqu'un qui offre, et les retourner
 * à la première personne demanderait un second jeu de textes dans trois
 * langues pour un parcours que le tunnel d'achat sait déjà tenir.
 */
enum QuizRelationship: string
{
    use HasTranslatedLabel;

    case Mother = 'mother';
    case Father = 'father';
    case Grandmother = 'grandmother';
    case Grandfather = 'grandfather';
    case Partner = 'partner';
    case Other = 'other';
    case Myself = 'myself';

    /**
     * Les liens qui mènent au quiz : tous sauf soi-même.
     *
     * Sans `array_values` : `Myself` est le dernier cas, le filtre ne laisse
     * donc pas de trou, et l'analyse statique le sait. Le jour où l'ordre
     * changerait, c'est elle qui le dirait — le type de retour ne serait plus
     * une liste.
     *
     * @return list<self>
     */
    public static function forOthers(): array
    {
        return array_filter(
            self::cases(),
            static fn (self $case): bool => $case !== self::Myself,
        );
    }

    /**
     * Le sujet dont parlent les écrans suivants : « votre mère ».
     *
     * Une clé, pas du texte : le quiz se lit dans cinq langues, et un groupe
     * nominal se décline autrement dans chacune.
     */
    public function subject(): string
    {
        return 'enums.quiz_subject.'.$this->value;
    }
}
