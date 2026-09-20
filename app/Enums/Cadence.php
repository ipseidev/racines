<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasTranslatedLabel;

/**
 * Rythme d'envoi des questions (PRD §5, P0-4).
 *
 * Deux grandeurs distinctes se cachent derrière le mot « rythme », et les
 * confondre a longtemps suffi tant qu'il n'y avait que deux cas :
 *
 *  - **combien de fois par semaine** une question part — un, deux ou trois
 *    jours dans la même semaine ;
 *  - **toutes les combien de semaines** le cycle recommence — une semaine
 *    sur une, ou une sur deux.
 *
 * `weeks()` ne portait que la seconde et suffisait à opposer l'hebdomadaire
 * au quinzomadaire. Les rythmes intra-semaine demandent la première, et avec
 * elle la question que personne ne posait : **quels jours**. Le narrateur en
 * choisit **un** — celui qu'il connaît, celui qu'il attend — et les autres
 * s'en déduisent par un écartement régulier : `dayOffsets()`. Deux fois par
 * semaine, c'est le mardi et le vendredi ; trois fois, le mardi, le jeudi et
 * le samedi. Jamais deux jours collés, parce que deux questions à vingt-quatre
 * heures d'écart se lisent comme une relance, et que le moteur passe sa vie à
 * ne pas relancer (bloc 09).
 */
enum Cadence: string
{
    use HasTranslatedLabel;

    case Weekly = 'weekly';
    case TwiceWeekly = 'twice_weekly';
    case ThriceWeekly = 'thrice_weekly';
    case Biweekly = 'biweekly';

    /** Le nombre de semaines d'un cycle : une, ou une sur deux. */
    public function weeks(): int
    {
        return match ($this) {
            self::Weekly, self::TwiceWeekly, self::ThriceWeekly => 1,
            self::Biweekly => 2,
        };
    }

    /** Le nombre de questions par cycle. */
    public function timesPerWeek(): int
    {
        return count($this->dayOffsets());
    }

    /**
     * Les jours d'envoi de la semaine, en décalage depuis le jour choisi.
     *
     * Toujours `0` en tête : le jour que le narrateur a choisi est le premier,
     * et il reste celui qu'il reconnaît dans son agenda.
     *
     * @return list<int>
     */
    public function dayOffsets(): array
    {
        return match ($this) {
            self::Weekly, self::Biweekly => [0],
            self::TwiceWeekly => [0, 3],
            self::ThriceWeekly => [0, 2, 4],
        };
    }

    /**
     * L'écart minimal entre deux questions, en jours.
     *
     * C'est cette borne, et non le seul jour de la semaine, qui décide de la
     * prochaine échéance : elle interdit qu'un changement de cadence ou de
     * jour fasse partir deux questions coup sur coup.
     */
    public function minimumGapDays(): int
    {
        return match ($this) {
            self::Weekly => 7,
            self::TwiceWeekly => 3,
            self::ThriceWeekly => 2,
            self::Biweekly => 14,
        };
    }

    /**
     * Les jours d'envoi de chaque cadence, indexés par valeur.
     *
     * L'interface a besoin des quatre en même temps : le choix change sans
     * aller-retour serveur, et la phrase qui dit « mardi et vendredi » doit
     * suivre le doigt. Les décalages viennent d'ici plutôt que d'une
     * constante du front, qui serait une seconde définition du domaine.
     *
     * @return array<string, list<int>>
     */
    public static function dayOffsetsByValue(): array
    {
        $offsets = [];

        foreach (self::cases() as $case) {
            $offsets[$case->value] = $case->dayOffsets();
        }

        return $offsets;
    }

    /** Un rythme d'au moins une question par semaine : tout sauf le quinzomadaire. */
    public function isAtLeastWeekly(): bool
    {
        return $this->weeks() === 1;
    }

    /**
     * Les rythmes à qui l'on peut encore proposer de ralentir.
     *
     * La règle « le rythme baisse » (bloc 09) ne visait que l'hebdomadaire,
     * puisque c'était le seul au-dessus du quinzomadaire. Nommer la condition
     * plutôt que la valeur évite qu'un rythme ajouté demain échappe
     * silencieusement à une règle écrite pour lui.
     *
     * @return list<string>
     */
    public static function atLeastWeeklyValues(): array
    {
        return array_values(array_map(
            static fn (self $case): string => $case->value,
            array_filter(self::cases(), static fn (self $case): bool => $case->isAtLeastWeekly()),
        ));
    }
}
