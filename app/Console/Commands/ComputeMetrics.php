<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Metrics\Metric;
use App\Metrics\Registry;
use App\Models\Project;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Calculer les métriques du pilote, chaque nuit.
 *
 * Trois propriétés, et chacune répond à une manière dont un tableau de bord
 * ment.
 *
 * **Rejouable.** `--date` permet de recalculer un jour passé. Une définition
 * de métrique se corrige toujours en cours de pilote — on découvre qu'un
 * dénominateur incluait des projets effacés, on corrige, et il faut alors
 * pouvoir refaire l'historique. Un tableau de bord qui ne se recalcule pas
 * fige ses propres erreurs.
 *
 * **Idempotente.** L'unicité est dans la base, pas dans le code : relancer
 * la commande écrase au lieu d'empiler. Une commande planifiée finit toujours
 * par tourner deux fois.
 *
 * **Par cohorte, et globalement.** Le pilote se lit par cohorte — deux vagues
 * de familles n'ont pas reçu le même produit — mais un chiffre global reste
 * nécessaire pour le comité. Les deux sont calculés, jamais l'un déduit de
 * l'autre : la moyenne des taux n'est pas le taux de l'ensemble.
 */
final class ComputeMetrics extends Command
{
    protected $signature = 'metrics:compute {--date= : la date à calculer ; hier sinon}';

    protected $description = 'Calcule les métriques du pilote pour une date, cohorte par cohorte';

    public function handle(): int
    {
        /*
         * Hier par défaut, et non aujourd'hui : une journée en cours donne
         * des chiffres qui changent à chaque exécution, et un tableau de bord
         * dont les nombres bougent sous les yeux ne se lit pas.
         */
        $date = CarbonImmutable::parse((string) ($this->option('date') ?: now()->subDay()->toDateString()))
            ->startOfDay();

        $cohortes = [null, ...Project::query()
            ->whereNotNull('cohort_id')
            ->distinct()
            ->pluck('cohort_id')
            ->all()];

        $ecrites = 0;
        $echecs = 0;

        foreach (Registry::all() as $metric) {
            foreach ($cohortes as $cohorte) {
                try {
                    $this->store($metric, $date, is_string($cohorte) ? $cohorte : null);
                    $ecrites++;
                } catch (Throwable $exception) {
                    /*
                     * Une métrique en échec n'arrête pas les autres.
                     *
                     * Un tableau de bord partiel est utilisable ; un tableau
                     * vide parce qu'une requête sur onze a une faute de
                     * frappe ne l'est pas. L'échec est consigné, et la ligne
                     * manquante se voit dans l'écran.
                     */
                    $echecs++;

                    Log::error('metrics.failed', [
                        'metric' => $metric->name(),
                        'cohort' => $cohorte,
                        'reason' => $exception->getMessage(),
                    ]);

                    $this->components->error(sprintf(
                        '%s (%s) : %s',
                        $metric->name(),
                        $cohorte ?? 'global',
                        $exception->getMessage(),
                    ));
                }
            }
        }

        $this->components->info(sprintf(
            '%d mesure(s) écrite(s) pour le %s%s.',
            $ecrites,
            $date->toDateString(),
            $echecs > 0 ? sprintf(', %d en échec', $echecs) : '',
        ));

        return $echecs > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function store(Metric $metric, CarbonImmutable $date, ?string $cohorte): void
    {
        $valeur = $metric->compute($date, $cohorte);

        DB::table('daily_metrics')->updateOrInsert(
            [
                'date' => $date->toDateString(),
                'cohort_id' => $cohorte,
                'metric' => $metric->name(),
            ],
            [
                'value' => $valeur->value,
                'numerator' => $valeur->numerator,
                'denominator' => $valeur->denominator,
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );
    }
}
