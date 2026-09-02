<?php

declare(strict_types=1);

namespace App\Engine;

use App\Enums\EngineAudience;
use App\Enums\EngineRuleId;
use App\Enums\ProjectStatus;
use App\Models\EngineEvent;
use App\Models\Project;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Le battement du moteur : toutes les heures, les onze règles dans l'ordre.
 *
 * Trois protections, dans cet ordre, et chacune répond à une façon dont ce
 * moteur pourrait devenir insupportable :
 *
 *  1. **Le projet est-il joignable ?** Une pause, un deuil, une résiliation :
 *     on se taît. Seule la confirmation de pause passe, parce qu'elle parle
 *     de la pause elle-même.
 *  2. **La limite de la règle** — deux rappels, une alerte par mois, un
 *     nudge par histoire. Chaque règle porte la sienne.
 *  3. **Un seul message au narrateur par jour** (règle §9). Quand deux règles
 *     veulent lui parler, celle qui vient en premier dans l'annexe C gagne ;
 *     l'autre est **consignée** comme supprimée, parce que savoir qu'elle
 *     aurait parlé fait partie de la mesure.
 *
 * Une règle qui plante ne bloque pas les suivantes : dix familles n'ont pas à
 * perdre leurs relances parce qu'une requête a mal tourné.
 */
final readonly class EngineTick
{
    /**
     * Les états où le produit ne sollicite personne.
     *
     * `dormant` en fait partie : un projet endormi se réveille par un acte de
     * la famille, pas par une relance de plus.
     *
     * @var list<ProjectStatus>
     */
    private const SILENT_STATUSES = [
        ProjectStatus::Draft,
        ProjectStatus::Paused,
        ProjectStatus::Dormant,
        ProjectStatus::Completed,
        ProjectStatus::Cancelled,
        ProjectStatus::FrozenBereavement,
    ];

    /**
     * @param  list<Rule>  $rules  Dans l'ordre de l'annexe C.
     */
    public function __construct(private array $rules) {}

    public function run(CarbonImmutable $now): TickReport
    {
        $report = new TickReport;

        /** @var array<string, EngineRuleId> $spokenToNarrator */
        $spokenToNarrator = [];

        foreach ($this->rules as $rule) {
            try {
                $occurrences = $rule->detect($now);
            } catch (Throwable $exception) {
                $report->failed++;

                Log::error('engine.rule_failed', [
                    'rule_id' => $rule->id()->value,
                    'message' => $exception->getMessage(),
                ]);

                continue;
            }

            foreach ($occurrences as $occurrence) {
                $this->handle($rule, $occurrence, $now, $report, $spokenToNarrator);
            }
        }

        Log::info('engine.tick', $report->toArray());

        return $report;
    }

    /**
     * @param  array<string, EngineRuleId>  $spokenToNarrator
     */
    private function handle(
        Rule $rule,
        Occurrence $occurrence,
        CarbonImmutable $now,
        TickReport $report,
        array &$spokenToNarrator,
    ): void {
        if (! self::isReachable($occurrence->project, $rule->id())) {
            $report->skipped++;

            return;
        }

        if ($rule->isCapped($occurrence)) {
            $report->skipped++;

            return;
        }

        // Déjà traitée : on ne la consigne pas une seconde fois, pas même
        // comme supprimée. La contrainte unique reste le filet contre deux
        // ticks simultanés ; cette lecture évite d'écrire du bruit tous les
        // jours pour une occurrence réglée.
        if (EngineEvent::query()->where('dedupe_key', $occurrence->dedupeKey($rule->id()))->exists()) {
            $report->skipped++;

            return;
        }

        $audience = $rule->audience($occurrence);

        $suppressedBy = $audience === EngineAudience::Narrator
            ? $spokenToNarrator[$occurrence->project->id]
                ?? self::narratorAlreadyToldToday($occurrence->project, $now)
            : null;

        try {
            DB::transaction(function () use ($rule, $occurrence, $now, $report, $suppressedBy, $audience, &$spokenToNarrator): void {
                // La ligne d'abord : sa clé unique est ce qui empêche deux
                // ticks concurrents d'envoyer deux fois le même message.
                $event = new EngineEvent([
                    'rule_id' => $rule->id(),
                    'occurrence_key' => $occurrence->occurrenceKey(),
                    // Un événement supprimé porte une clé **datée**, distincte
                    // de la vraie : sans ça il consommerait l'idempotence de
                    // l'occurrence, et le rappel qu'on a seulement différé ne
                    // partirait jamais.
                    'dedupe_key' => $suppressedBy instanceof EngineRuleId
                        ? $occurrence->dedupeKey($rule->id()).':suppressed:'.$now->toDateString()
                        : $occurrence->dedupeKey($rule->id()),
                    'fired_at' => $now,
                ]);

                $event->project()->associate($occurrence->project);

                if ($occurrence->story !== null) {
                    $event->story()->associate($occurrence->story);
                }

                $event->save();

                if ($suppressedBy instanceof EngineRuleId) {
                    // Consigné, pas envoyé. La clé diffère de `told` exprès :
                    // un événement supprimé n'a parlé à personne, et ne doit
                    // donc pas compter comme un message du jour.
                    $event->action_taken = [
                        'suppressed_by' => $suppressedBy->value,
                        'would_have_told' => $audience->value,
                    ];
                    $event->save();

                    $report->suppressed++;

                    return;
                }

                $event->action_taken = [
                    'told' => $audience->value,
                    ...$rule->fire($occurrence),
                ];
                $event->save();

                if ($audience === EngineAudience::Narrator) {
                    $spokenToNarrator[$occurrence->project->id] = $rule->id();
                }

                $report->fired++;
            });
        } catch (QueryException $exception) {
            // Violation de la clé unique : l'occurrence a déjà été traitée.
            // C'est le chemin normal d'un tick rejoué, pas une erreur.
            if (! str_contains($exception->getMessage(), 'engine_events_dedupe_key_unique')) {
                throw $exception;
            }

            $report->skipped++;
        }
    }

    /**
     * Le projet accepte-t-il d'être sollicité ?
     *
     * La confirmation de pause est la seule exception : sans elle, le
     * narrateur ne saurait jamais que sa pause a été prise en compte — et
     * c'est précisément le message qu'il attend.
     */
    private static function isReachable(Project $project, EngineRuleId $rule): bool
    {
        if ($rule === EngineRuleId::PauseRequested) {
            return true;
        }

        if (in_array($project->status, self::SILENT_STATUSES, true)) {
            return false;
        }

        return ! $project->isPaused();
    }

    /**
     * A-t-on déjà parlé au narrateur de ce projet aujourd'hui ?
     *
     * On interroge la base et pas seulement la mémoire du tick : le tick
     * tourne toutes les heures, et « le même jour » couvre vingt-quatre
     * battements.
     */
    private static function narratorAlreadyToldToday(Project $project, CarbonImmutable $now): ?EngineRuleId
    {
        $event = EngineEvent::query()
            ->where('project_id', $project->id)
            ->whereBetween('fired_at', [$now->startOfDay(), $now->endOfDay()])
            ->whereJsonContains('action_taken->told', EngineAudience::Narrator->value)
            ->orderBy('fired_at')
            ->first();

        return $event?->rule_id;
    }
}
