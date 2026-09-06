<?php

declare(strict_types=1);

namespace App\Metrics;

use App\Models\Project;
use Carbon\CarbonImmutable;

/**
 * H1 : huit histoires validées à J70, **en intention de traiter**.
 *
 * Seuil du dossier : **ITT ≥ 50 %**, activés ≥ 65 % (R-5).
 *
 * Le dénominateur est le critère de sortie du bloc, et c'est le chiffre le
 * plus facile à embellir sans mentir : **les accepteurs, y compris ceux qui
 * n'ont jamais rien enregistré**. Compter sur les « activés » — ceux qui ont
 * fait au moins une histoire — retirerait du dénominateur exactement les
 * familles où le produit a échoué le plus tôt, et le taux monterait sans que
 * rien ne s'améliore.
 *
 * C'est la raison d'être de l'intention de traiter : on juge le produit sur
 * tous ceux à qui il a été proposé, pas sur ceux qui s'en sont accommodés.
 *
 * J70 se compte depuis **l'acceptation**, qui est le moment où le narrateur
 * entre dans le produit.
 */
final readonly class H1Itt8StoriesJ70 implements Metric
{
    private const JOURS = 70;

    private const OBJECTIF = 8;

    public function name(): string
    {
        return 'h1_itt_8_stories_j70';
    }

    public function definition(): string
    {
        return 'Part des **accepteurs** (y compris jamais activés) ayant 8 histoires validées '
            .'à J70 après acceptation. Seuil R-5 : ≥ 50 %. Compter sur les activés retirerait '
            .'du dénominateur les familles où le produit a échoué le plus tôt.';
    }

    public function compute(CarbonImmutable $date, ?string $cohort): MetricValue
    {
        $limite = $date->endOfDay()->subDays(self::JOURS);

        // Les accepteurs assez anciens pour avoir eu leurs soixante-dix jours.
        $accepteurs = Project::query()
            ->whereNotNull('accepted_at')
            ->where('accepted_at', '<=', $limite)
            ->when($cohort !== null, fn ($q) => $q->where('cohort_id', $cohort));

        $atteints = (clone $accepteurs)
            ->whereRaw(
                '(select count(*) from stories where stories.project_id = projects.id '
                .'and stories.validated_at is not null '
                ."and stories.validated_at <= projects.accepted_at + interval '70 days') >= ?",
                [self::OBJECTIF],
            );

        return MetricValue::rate($atteints->count(), $accepteurs->count());
    }
}
