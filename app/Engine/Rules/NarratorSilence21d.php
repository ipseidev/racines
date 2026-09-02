<?php

declare(strict_types=1);

namespace App\Engine\Rules;

use App\Engine\Actions\AckCallParent;
use App\Engine\Actions\OfferPhoneOption;
use App\Engine\Actions\OneTapRegistry;
use App\Engine\Actions\ResendWhatsapp;
use App\Engine\Actions\SwitchBiweekly;
use App\Engine\BaseRule;
use App\Engine\InitiatorLoad;
use App\Engine\Occurrence;
use App\Enums\EngineAudience;
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
 * Trois semaines sans un mot : on en parle à l'Initiateur·rice.
 *
 * Quatre gestes possibles, et un seul tap chacun. Le message ne dramatise
 * pas — « ce n'est pas grave, et ça se débloque souvent d'un coup de fil » —
 * parce que la personne qui le lit se sent déjà responsable, et qu'un
 * reproche la ferait renoncer plutôt qu'agir.
 *
 * Une alerte par mois. Au-delà, ce n'est plus une alerte, c'est un rappel de
 * son échec.
 */
final class NarratorSilence21d extends BaseRule
{
    public function __construct(private readonly TokenService $tokens) {}

    public function id(): EngineRuleId
    {
        return EngineRuleId::NarratorSilence21d;
    }

    public function audience(Occurrence $occurrence): EngineAudience
    {
        return EngineAudience::Initiator;
    }

    public function detect(CarbonImmutable $now): Collection
    {
        $days = (int) config('product.engine.silence_alert_days');

        return Project::query()
            ->with(['owner', 'primaryNarrator'])
            ->where('status', ProjectStatus::Active->value)
            ->whereNotNull('accepted_at')
            ->whereDoesntHave(
                'stories',
                // Strictement plus récente : à dix jours pile, le silence
                // dure bien dix jours, et la règle doit parler.
                fn ($query) => $query->where('recorded_at', '>', $now->subDays($days)),
            )
            ->get()
            ->map(fn (Project $project): Occurrence => new Occurrence(
                project: $project,
                narrator: $project->primaryNarrator,
                key: 'alert',
                attempt: $this->firedFor($project) + 1,
            ))
            ->values();
    }

    public function isCapped(Occurrence $occurrence): bool
    {
        if (InitiatorLoad::isSaturated($occurrence->project)) {
            return true;
        }

        $days = (int) config('product.engine.silence_alert_min_interval_days');

        return $this->firedFor($occurrence->project, CarbonImmutable::now()->subDays($days)) >= 1;
    }

    public function fire(Occurrence $occurrence): array
    {
        $project = $occurrence->project;
        $narrator = $occurrence->narrator === null ? '' : $occurrence->narrator->first_name;

        $actions = [
            [ResendWhatsapp::name(), __('notifications.engine.initiator_alert.button')],
            [SwitchBiweekly::name(), __('notifications.engine.initiator_alert.switch')],
            [AckCallParent::name(), __('notifications.engine.initiator_alert.call', ['narrator' => $narrator])],
        ];

        // Le quatrième geste n'est proposé que s'il peut être tenu : une
        // promesse humaine faite à plus de familles qu'on ne peut en rappeler
        // vaut moins qu'une promesse jamais faite.
        if (OfferPhoneOption::isAvailableFor($project)) {
            $actions[] = [OfferPhoneOption::name(), __('notifications.engine.initiator_alert.phone')];
        }

        $links = [];

        foreach ($actions as [$name, $label]) {
            $issued = $this->tokens->issue(
                TokenType::Action,
                $project,
                OneTapRegistry::scopeFor($name),
                now()->addDays(30),
            );

            $links[] = ['label' => $label, 'url' => Links::action($issued->plain)];
        }

        return [
            ...$this->tell(
                $project->owner,
                $occurrence,
                'initiator_alert',
                ['narrator' => $narrator],
                $links,
            ),
            'offered' => array_column($actions, 0),
        ];
    }

    public function resumed(EngineEvent $event, CarbonImmutable $now): ?bool
    {
        $recorded = Story::query()
            ->where('project_id', $event->project_id)
            ->where('recorded_at', '>=', $event->fired_at)
            ->exists();

        if ($recorded) {
            return true;
        }

        // Trente jours : c'est la fenêtre que le dossier retient pour cette
        // alerte (`recorded_within_30d_after_alert`).
        return $event->fired_at->lte($now->subDays(30)) ? false : null;
    }
}
