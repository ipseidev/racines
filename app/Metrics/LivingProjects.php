<?php

declare(strict_types=1);

namespace App\Metrics;

use App\Models\Project;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * La North Star : les **projets vivants**.
 *
 * Sa définition est le choix le plus lourd du bloc, parce qu'une North Star
 * mal choisie oriente tout le produit. Ici : un projet est vivant s'il a, dans
 * les trente derniers jours, **au moins une histoire validée qui a été
 * écoutée trente secondes par un proche**.
 *
 * Les trois conditions comptent, et aucune ne suffit :
 *
 *  - **Validée** et non enregistrée : un récit que le narrateur n'a pas
 *    approuvé n'existe pour personne d'autre que lui.
 *  - **Écoutée** et non partagée : un lien envoyé que personne n'ouvre ne
 *    fait pas une famille vivante, et c'est exactement l'illusion que
 *    « nombre d'histoires » entretiendrait.
 *  - **Trente secondes** : le seuil du dossier, celui qui distingue une page
 *    ouverte par curiosité d'une écoute réelle.
 *
 * Ce que la métrique **refuse** de compter : les projets qui produisent sans
 * être écoutés, et ceux qu'on écoute sans qu'ils produisent. La boucle
 * entière, ou rien.
 */
final readonly class LivingProjects implements Metric
{
    private const JOURS = 30;

    public function name(): string
    {
        return 'living_projects';
    }

    public function definition(): string
    {
        return 'Projets ayant, dans les 30 derniers jours, au moins une histoire validée '
            .'écoutée ≥ 30 s par un proche. Dénominateur : les projets actifs.';
    }

    public function compute(CarbonImmutable $date, ?string $cohort): MetricValue
    {
        $depuis = $date->subDays(self::JOURS);

        $actifs = Project::query()
            ->whereNull('erased_at')
            ->when($cohort !== null, fn ($q) => $q->where('cohort_id', $cohort))
            ->whereNotNull('accepted_at')
            ->where('accepted_at', '<=', $date->endOfDay());

        $vivants = (clone $actifs)
            ->whereExists(function ($query) use ($depuis, $date): void {
                $query->select(DB::raw(1))
                    ->from('listen_events')
                    ->join('stories', 'stories.id', '=', 'listen_events.story_id')
                    ->whereColumn('stories.project_id', 'projects.id')
                    ->whereNotNull('stories.validated_at')
                    ->where('listen_events.reached_30s', true)
                    // Un proche, pas le narrateur qui se réécoute : la
                    // North Star mesure une **boucle familiale**.
                    ->whereNotNull('listen_events.family_member_id')
                    ->whereBetween('listen_events.updated_at', [$depuis, $date->endOfDay()]);
            });

        return MetricValue::rate($vivants->count(), $actifs->count());
    }
}
