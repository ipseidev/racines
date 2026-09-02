<?php

declare(strict_types=1);

namespace App\Engine\Rules;

use App\Engine\Actions\OneTapRegistry;
use App\Engine\Actions\SwitchBiweekly;
use App\Engine\BaseRule;
use App\Engine\Occurrence;
use App\Enums\Cadence;
use App\Enums\EngineRuleId;
use App\Enums\ProjectStatus;
use App\Enums\TokenType;
use App\Models\EngineEvent;
use App\Models\Project;
use App\Models\Story;
use App\Services\Tokens\TokenService;
use App\Support\Links;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Le rythme ralentit : on propose de ralentir **officiellement**.
 *
 * « Réduire vaut mieux qu'arrêter. » Quelqu'un qui passe de quatre histoires
 * à une n'est pas en train de se désintéresser : il est en train de trouver
 * le rythme trop soutenu, et la seule issue qu'il connaisse est d'arrêter.
 *
 * Le seuil est prudent — au moins deux histoires devenues une au plus — parce
 * qu'une baisse de un à zéro n'est pas un ralentissement, c'est un silence, et
 * une autre règle s'en occupe avec d'autres mots.
 *
 * Hebdomadaire, et une proposition toutes les huit semaines : deux fois de
 * suite, ce serait insister sur un refus.
 */
final class DecliningCadence extends BaseRule
{
    public function __construct(private readonly TokenService $tokens) {}

    public function id(): EngineRuleId
    {
        return EngineRuleId::DecliningCadence;
    }

    public function detect(CarbonImmutable $now): Collection
    {
        // Une fois par semaine : le lundi matin, au tick de 7 h 07.
        if ($now->dayOfWeek !== CarbonImmutable::MONDAY || $now->hour !== 7) {
            return collect();
        }

        $weeks = (int) config('product.engine.declining_window_weeks');
        $recentFrom = $now->subWeeks($weeks);
        $previousFrom = $now->subWeeks($weeks * 2);

        return Project::query()
            ->with('primaryNarrator')
            ->where('status', ProjectStatus::Active->value)
            ->where('cadence', Cadence::Weekly->value)
            ->get()
            ->filter(function (Project $project) use ($recentFrom, $previousFrom): bool {
                $recent = self::recordedBetween($project, $recentFrom, null);
                $previous = self::recordedBetween($project, $previousFrom, $recentFrom);

                // Le minimum du dossier : au moins deux, devenues une au plus.
                return $previous >= 2 && $recent <= intdiv($previous, 2);
            })
            ->map(fn (Project $project): Occurrence => new Occurrence(
                project: $project,
                narrator: $project->primaryNarrator,
                key: 'cadence',
                attempt: $this->firedFor($project) + 1,
            ))
            ->values();
    }

    public function isCapped(Occurrence $occurrence): bool
    {
        $weeks = (int) config('product.engine.declining_offer_min_interval_weeks');

        return $this->firedFor($occurrence->project, CarbonImmutable::now()->subWeeks($weeks)) >= 1;
    }

    public function fire(Occurrence $occurrence): array
    {
        $narrator = $occurrence->narrator;

        if ($narrator === null) {
            return ['skipped' => 'no_narrator'];
        }

        $issued = $this->tokens->issue(
            TokenType::Action,
            $occurrence->project,
            OneTapRegistry::scopeFor(SwitchBiweekly::name()),
            now()->addDays(30),
        );

        return $this->tell(
            $narrator,
            $occurrence,
            'slower_rhythm_offer',
            [],
            [[
                'label' => __('notifications.engine.slower_rhythm_offer.button'),
                'url' => Links::action($issued->plain),
            ]],
        );
    }

    private static function recordedBetween(Project $project, CarbonImmutable $from, ?CarbonImmutable $to): int
    {
        return Story::query()
            ->where('project_id', $project->id)
            ->whereNotNull('recorded_at')
            ->where('recorded_at', '>=', $from)
            ->when($to !== null, fn ($query) => $query->where('recorded_at', '<', $to))
            ->count();
    }

    /**
     * La reprise se mesure loin : ce qu'on veut savoir, c'est si la famille
     * est toujours là huit semaines plus tard.
     */
    public function resumed(EngineEvent $event, CarbonImmutable $now): ?bool
    {
        if ($event->fired_at->gt($now->subWeeks(8))) {
            return null;
        }

        return Story::query()
            ->where('project_id', $event->project_id)
            ->where('recorded_at', '>=', $event->fired_at)
            ->exists();
    }
}
