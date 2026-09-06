<?php

declare(strict_types=1);

namespace App\Metrics;

use Carbon\CarbonImmutable;

/**
 * Une mesure du pilote.
 *
 * Une classe par métrique, et non une commande de deux cents lignes : chaque
 * définition doit pouvoir se lire seule, avec le commentaire qui dit **ce
 * qu'elle compte et pourquoi ce dénominateur-là**. C'est ce qui distingue un
 * chiffre défendable d'un chiffre produit par une requête que personne ne
 * relit.
 */
interface Metric
{
    /** Le nom stocké, stable : il sert de clé dans la table et les tableaux. */
    public function name(): string;

    /**
     * Ce que la métrique mesure, en une phrase, avec son dénominateur.
     *
     * Rendue par `metrics:describe` et recopiée dans le runbook : une
     * métrique dont la définition vit seulement dans du code SQL finit par
     * être citée de travers dans une présentation au comité.
     */
    public function definition(): string;

    /**
     * @param  string|null  $cohort  `null` = toutes cohortes confondues
     */
    public function compute(CarbonImmutable $date, ?string $cohort): MetricValue;
}
