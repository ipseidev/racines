<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\EngineOutcome;
use App\Enums\EngineRuleId;
use App\Models\EngineEvent;
use Illuminate\Console\Command;

/**
 * Ce que le moteur a fait ces trente derniers jours, règle par règle.
 *
 * Le tableau qu'on regarde quand on se demande si le moteur sert à quelque
 * chose. Trois colonnes comptent : les déclenchements, les reprises, et le
 * **délai médian** — une relance qui fonctionne au bout de trois heures et une
 * qui fonctionne au bout de vingt jours ne disent pas la même chose du
 * produit.
 *
 * La médiane et non la moyenne : une reprise à trente jours tire une moyenne
 * et fait croire que le moteur est lent alors qu'il est le plus souvent
 * rapide.
 */
final class EngineReport extends Command
{
    protected $signature = 'engine:report {--days=30 : Fenêtre observée}';

    protected $description = 'Déclenchements et reprises du moteur de complétion';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $since = now()->subDays($days);

        $rows = [];

        foreach (EngineRuleId::cases() as $rule) {
            $events = EngineEvent::query()
                ->where('rule_id', $rule->value)
                ->where('fired_at', '>=', $since)
                ->whereRaw("action_taken ->> 'told' is not null")
                ->get();

            if ($events->isEmpty()) {
                continue;
            }

            $resumed = $events->where('outcome', EngineOutcome::Resumed);

            $delays = $resumed
                ->filter(fn (EngineEvent $event): bool => $event->outcome_at !== null)
                ->map(fn (EngineEvent $event): float => $event->fired_at->diffInHours($event->outcome_at))
                ->sort()
                ->values();

            /** @var list<float> $sorted */
            $sorted = array_values($delays->all());

            $rows[] = [
                $rule->value,
                (string) $events->count(),
                (string) $resumed->count(),
                $events->count() === 0
                    ? '—'
                    : number_format($resumed->count() / $events->count() * 100, 1).' %',
                $sorted === [] ? '—' : number_format(self::median($sorted), 1).' h',
            ];
        }

        if ($rows === []) {
            $this->components->info("Aucun déclenchement sur {$days} jours.");

            return self::SUCCESS;
        }

        $this->table(
            ['règle', 'déclenchements', 'reprises', 'taux', 'délai médian'],
            $rows,
        );

        return self::SUCCESS;
    }

    /**
     * @param  list<float>  $values  Déjà triées.
     */
    private static function median(array $values): float
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
