<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Engine\EngineTick;
use App\Engine\RuleRegistry;
use App\Services\Analytics\Analytics;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

/**
 * Le battement du moteur, toutes les heures à la minute sept.
 *
 * Une minute décalée plutôt que zéro : à l'heure ronde, tout ce qui tourne sur
 * la machine se réveille en même temps, et un tick qui attend son tour de base
 * de données prend du retard sur toute la journée.
 */
final class EngineTickCommand extends Command
{
    protected $signature = 'engine:tick';

    protected $description = 'Exécute les onze règles du moteur de complétion';

    public function handle(RuleRegistry $rules, Analytics $analytics): int
    {
        $report = (new EngineTick($rules->all(), $analytics))->run(CarbonImmutable::now());

        $this->components->info(sprintf(
            '%d déclenchement(s), %d supprimé(s), %d ignoré(s), %d en échec.',
            $report->fired,
            $report->suppressed,
            $report->skipped,
            $report->failed,
        ));

        // Une règle en échec ne fait pas échouer le tick : les dix autres ont
        // travaillé. Le journal porte le détail, et la supervision le verra.
        return self::SUCCESS;
    }
}
