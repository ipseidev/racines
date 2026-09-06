<?php

declare(strict_types=1);

namespace App\Metrics;

/**
 * Le résultat d'une mesure.
 *
 * Numérateur et dénominateur **en plus** de la valeur, et c'est le point : un
 * « 62 % » ne dit pas s'il porte sur cinquante familles ou sur trois. Le
 * dossier fixe des seuils — H0 ≥ 60 %, H1 ≥ 50 % — qu'il serait absurde de
 * déclarer atteints sur un échantillon de trois, et c'est exactement l'erreur
 * qu'un tableau de bord encourage quand il n'affiche que le taux.
 */
final readonly class MetricValue
{
    public function __construct(
        public float $value,
        public ?int $numerator = null,
        public ?int $denominator = null,
    ) {}

    /**
     * Un taux, ou zéro quand il n'y a rien à diviser.
     *
     * Zéro et non `null` : une cohorte sans acceptation a un taux de 0 %, ce
     * qui est une information. `null` la ferait disparaître du tableau, et
     * l'absence se lit comme « pas encore mesuré » — le contraire.
     */
    public static function rate(int $numerator, int $denominator): self
    {
        return new self(
            $denominator === 0 ? 0.0 : round($numerator / $denominator, 4),
            $numerator,
            $denominator,
        );
    }

    public static function count(int $count): self
    {
        return new self((float) $count, $count);
    }
}
