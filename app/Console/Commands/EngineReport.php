<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Engine\CompletionReport;
use Illuminate\Console\Command;

/**
 * Ce que le moteur a fait ces trente derniers jours, règle par règle.
 *
 * Le tableau qu'on regarde quand on se demande si le moteur sert à quelque
 * chose. Le calcul vit dans `CompletionReport`, partagé avec la page du
 * back-office : deux implémentations donneraient deux chiffres pour la même
 * question.
 */
final class EngineReport extends Command
{
    protected $signature = 'engine:report {--days=30 : Fenêtre observée} {--cohort= : Restreindre à une cohorte}';

    protected $description = 'Déclenchements et reprises du moteur de complétion';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cohort = $this->option('cohort');

        $rows = CompletionReport::rows($days, is_string($cohort) ? $cohort : null);

        if ($rows === []) {
            $this->components->info("Aucun déclenchement sur {$days} jours.");

            return self::SUCCESS;
        }

        $this->table(
            ['règle', 'déclenchements', 'reprises', 'taux', 'délai médian'],
            array_map(fn (array $row): array => [
                $row['rule'],
                (string) $row['fired'],
                (string) $row['resumed'],
                $row['rate'] === null ? '—' : number_format($row['rate'], 1).' %',
                $row['median_hours'] === null ? '—' : number_format($row['median_hours'], 1).' h',
            ], $rows),
        );

        return self::SUCCESS;
    }
}
