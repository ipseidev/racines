<?php

declare(strict_types=1);

namespace App\Metrics;

use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Une contre-métrique : un simple comptage sur une période.
 *
 * Les contre-métriques du PRD §7 existent pour **empêcher un chiffre
 * flatteur**. Un taux de validation excellent accompagné de retraits massifs
 * dirait qu'on a poussé des gens à partager ce qu'ils ne voulaient pas ; un
 * H0 magnifique accompagné de remboursements dirait qu'on vend mal.
 *
 * Une seule classe paramétrée plutôt que huit quasi identiques : ce qui
 * distingue « histoires masquées » de « remboursements » est une table et une
 * colonne, pas une logique. Huit fichiers de trente lignes se copieraient les
 * uns les autres, et le neuvième oublierait le filtre de cohorte.
 */
final readonly class CounterMetrics implements Metric
{
    private const JOURS = 30;

    public function __construct(
        private string $name,
        private string $definition,
        private string $table,
        private string $dateColumn,
        /** @var callable(Builder): void|null */
        private mixed $filter = null,
        private string $projectJoin = 'project_id',
    ) {}

    public function name(): string
    {
        return $this->name;
    }

    public function definition(): string
    {
        return $this->definition;
    }

    public function compute(CarbonImmutable $date, ?string $cohort): MetricValue
    {
        $query = DB::table($this->table)
            ->whereNotNull($this->table.'.'.$this->dateColumn)
            ->whereBetween($this->table.'.'.$this->dateColumn, [
                $date->subDays(self::JOURS)->startOfDay(),
                $date->endOfDay(),
            ]);

        if ($cohort !== null) {
            $query->join('projects', 'projects.id', '=', $this->table.'.'.$this->projectJoin)
                ->where('projects.cohort_id', $cohort);
        }

        if ($this->filter !== null) {
            ($this->filter)($query);
        }

        return MetricValue::count($query->count());
    }
}
