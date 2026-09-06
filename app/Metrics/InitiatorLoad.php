<?php

declare(strict_types=1);

namespace App\Metrics;

use App\Models\Project;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * La charge de l'Initiateur·rice, par mois.
 *
 * Le dossier lui promet **≤ 4 sollicitations et ≤ 15 minutes par mois**.
 * Cette promesse est la contrepartie de tout le produit : c'est elle qui
 * distingue « offrir un cadeau » de « prendre un second travail », et c'est
 * la première chose qu'une famille abandonnera si elle n'est pas tenue.
 *
 * Sans mesure, la promesse est une intention. Le moteur de complétion, qui
 * envoie des relances, est précisément la pièce qui peut la rompre sans que
 * personne s'en aperçoive — chaque règle prise isolément semble raisonnable.
 *
 * On compte les **sollicitations envoyées**, pas les actions faites : une
 * demande ignorée pèse quand même sur la personne à qui elle arrive.
 */
final readonly class InitiatorLoad implements Metric
{
    private const JOURS = 30;

    public function name(): string
    {
        return 'initiator_requests_per_month';
    }

    public function definition(): string
    {
        return 'Sollicitations envoyées à l’Initiateur·rice par projet actif sur 30 jours. '
            .'Seuil du dossier : ≤ 4. On compte les demandes envoyées, pas les actions faites — '
            .'une demande ignorée pèse quand même sur la personne à qui elle arrive.';
    }

    public function compute(CarbonImmutable $date, ?string $cohort): MetricValue
    {
        $depuis = $date->subDays(self::JOURS)->startOfDay();
        $fin = $date->endOfDay();

        $projets = Project::query()
            ->whereNull('erased_at')
            ->whereNotNull('accepted_at')
            ->where('accepted_at', '<=', $fin)
            ->when($cohort !== null, fn ($q) => $q->where('cohort_id', $cohort));

        $actifs = $projets->count();

        $sollicitations = DB::table('outbound_messages')
            ->whereIn('project_id', (clone $projets)->select('id'))
            // Les messages adressés à l'Initiateur·rice se reconnaissent à
            // leur gabarit : ceux du moteur qui lui demandent quelque chose.
            ->where('template', 'like', 'engine_initiator%')
            ->whereBetween('created_at', [$depuis, $fin])
            ->count();

        // Une moyenne par projet : le total brut monterait avec les ventes et
        // ne dirait rien de ce que **chaque** personne subit.
        return new MetricValue(
            $actifs === 0 ? 0.0 : round($sollicitations / $actifs, 4),
            $sollicitations,
            $actifs,
        );
    }
}
