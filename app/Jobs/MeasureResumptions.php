<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Engine\RuleRegistry;
use App\Enums\AnalyticsEvent;
use App\Enums\EngineOutcome;
use App\Models\EngineEvent;
use App\Services\Analytics\Analytics;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Ce que les relances ont produit.
 *
 * C'est ce job qui transforme le moteur en actif défendable : sans lui, on
 * saurait combien de messages sont partis, pas si l'un d'eux a servi. Chaque
 * règle sait reconnaître **sa** reprise — un lien ouvert, un enregistrement
 * arrivé, une réaction envoyée — et le dit en trois états : oui, non, ou pas
 * encore.
 *
 * On ne repasse pas indéfiniment : au-delà de trente jours, un événement sans
 * verdict est classé sans effet. Un « peut-être » qui traîne un an ne mesure
 * rien.
 */
final class MeasureResumptions implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public function __construct()
    {
        $this->onQueue('default');
    }

    public function handle(RuleRegistry $rules, Analytics $analytics): void
    {
        $now = CarbonImmutable::now();
        $measured = 0;

        EngineEvent::query()
            ->with('project')
            ->whereNull('outcome')
            ->where('fired_at', '>=', $now->subDays(30))
            ->whereRaw("action_taken ->> 'told' is not null")
            ->cursor()
            ->each(function (EngineEvent $event) use ($rules, $analytics, $now, &$measured): void {
                $rule = $rules->find($event->rule_id);

                if ($rule === null) {
                    return;
                }

                $verdict = $rule->resumed($event, $now);

                if ($verdict === null) {
                    return;
                }

                $event->outcome = $verdict ? EngineOutcome::Resumed : EngineOutcome::NoEffect;
                $event->outcome_at = $now;
                $event->save();
                $measured++;

                if ($verdict) {
                    $analytics->capture(AnalyticsEvent::EngineRuleResumed, [
                        'rule_id' => $event->rule_id->value,
                        'project_id' => $event->project_id,
                        'delay_hours' => (int) $event->fired_at->diffInHours($now),
                    ], $event->project_id);
                }
            });

        // Passé trente jours sans verdict, on tranche : un « peut-être » qui
        // traîne un an ne mesure rien.
        $expired = EngineEvent::query()
            ->whereNull('outcome')
            ->where('fired_at', '<', $now->subDays(30))
            ->whereRaw("action_taken ->> 'told' is not null")
            ->update(['outcome' => EngineOutcome::NoEffect->value, 'outcome_at' => $now]);

        Log::info('engine.resumptions_measured', [
            'measured' => $measured,
            'expired' => $expired,
        ]);
    }
}
