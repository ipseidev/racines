<?php

declare(strict_types=1);

namespace App\Metrics;

use App\Models\Project;
use Carbon\CarbonImmutable;

/**
 * H1, mesure **secondaire** : les activés.
 *
 * Seuil du dossier : ≥ 65 % (R-5).
 *
 * Elle se lit toujours **à côté** de l'ITT et jamais à sa place. Prise seule,
 * elle flatte : un produit qui perdrait neuf familles sur dix dès la première
 * semaine afficherait un excellent taux d'activés, puisque les neuf
 * disparaîtraient du dénominateur.
 *
 * Son utilité est ailleurs, et elle est réelle : l'écart entre les deux
 * chiffres **dit où le produit casse**. ITT bas et activés hauts, c'est
 * l'entrée qui échoue — le lien, le micro, la première fois. Les deux bas,
 * c'est la répétition.
 */
final readonly class H1Activated implements Metric
{
    private const JOURS = 70;

    private const OBJECTIF = 8;

    public function name(): string
    {
        return 'h1_activated_8_stories_j70';
    }

    public function definition(): string
    {
        return 'Part des **activés** (≥ 1 histoire enregistrée) ayant 8 histoires validées à J70. '
            .'Seuil R-5 : ≥ 65 %. À lire à côté de l’ITT, jamais à sa place : l’écart entre les '
            .'deux dit si le produit casse à l’entrée ou à la répétition.';
    }

    public function compute(CarbonImmutable $date, ?string $cohort): MetricValue
    {
        $limite = $date->endOfDay()->subDays(self::JOURS);

        $actives = Project::query()
            ->whereNotNull('accepted_at')
            ->where('accepted_at', '<=', $limite)
            ->when($cohort !== null, fn ($q) => $q->where('cohort_id', $cohort))
            ->whereHas('stories', fn ($q) => $q->whereNotNull('recorded_at'));

        $atteints = (clone $actives)
            ->whereRaw(
                '(select count(*) from stories where stories.project_id = projects.id '
                .'and stories.validated_at is not null '
                ."and stories.validated_at <= projects.accepted_at + interval '70 days') >= ?",
                [self::OBJECTIF],
            );

        return MetricValue::rate($atteints->count(), $actives->count());
    }
}
