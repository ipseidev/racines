<?php

declare(strict_types=1);

namespace App\Engine;

use App\Enums\EngineOutcome;
use App\Enums\EngineRuleId;
use App\Models\EngineEvent;

/**
 * Ce que le moteur a fait, règle par règle.
 *
 * Le calcul vit ici et non dans la commande, parce qu'il est lu à deux
 * endroits : `engine:report` en console, et la page du back-office. Deux
 * implémentations donneraient deux chiffres pour la même question, et c'est
 * exactement ce dont on ne peut pas se permettre de discuter dans une revue
 * de pilote.
 *
 * Trois colonnes comptent : les déclenchements, les reprises, et le **délai
 * médian** — une relance qui fonctionne au bout de trois heures et une qui
 * fonctionne au bout de vingt jours ne disent pas la même chose du produit.
 *
 * La médiane et non la moyenne : une reprise à trente jours tire une moyenne
 * et fait croire que le moteur est lent alors qu'il est le plus souvent
 * rapide.
 */
final class CompletionReport
{
    /**
     * @return list<array{
     *     rule: string,
     *     fired: int,
     *     resumed: int,
     *     rate: float|null,
     *     median_hours: float|null,
     * }>
     */
    public static function rows(int $days = 30, ?string $cohortId = null): array
    {
        $since = now()->subDays($days);
        $rows = [];

        foreach (EngineRuleId::cases() as $rule) {
            $query = EngineEvent::query()
                ->where('rule_id', $rule->value)
                ->where('fired_at', '>=', $since)
                // Les événements **envoyés** seulement : une ligne supprimée
                // par le plafond quotidien n'a rien dit à personne, et la
                // compter gonflerait le dénominateur (T-99).
                ->whereRaw("action_taken ->> 'told' is not null");

            if ($cohortId !== null && $cohortId !== '') {
                $query->whereHas('project', fn ($project) => $project->where('cohort_id', $cohortId));
            }

            $events = $query->get();

            if ($events->isEmpty()) {
                continue;
            }

            $resumed = $events->where('outcome', EngineOutcome::Resumed);

            $delays = array_values($resumed
                ->filter(fn (EngineEvent $event): bool => $event->outcome_at !== null)
                ->map(fn (EngineEvent $event): float => $event->fired_at->diffInHours($event->outcome_at))
                ->sort()
                ->values()
                ->all());

            $rows[] = [
                'rule' => $rule->value,
                'fired' => $events->count(),
                'resumed' => $resumed->count(),
                'rate' => $resumed->count() / $events->count() * 100,
                'median_hours' => $delays === [] ? null : self::median($delays),
            ];
        }

        return $rows;
    }

    /**
     * @param  list<float>  $values  Déjà triées.
     */
    public static function median(array $values): float
    {
        $count = count($values);

        if ($count === 0) {
            return 0.0;
        }

        $middle = intdiv($count, 2);

        return $count % 2 === 1
            ? $values[$middle]
            : ($values[$middle - 1] + $values[$middle]) / 2;
    }
}
