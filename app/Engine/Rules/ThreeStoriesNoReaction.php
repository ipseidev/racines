<?php

declare(strict_types=1);

namespace App\Engine\Rules;

use App\Engine\Actions\OneTapRegistry;
use App\Engine\Actions\ReactHeart;
use App\Engine\BaseRule;
use App\Engine\InitiatorLoad;
use App\Engine\Occurrence;
use App\Enums\EngineAudience;
use App\Enums\EngineRuleId;
use App\Enums\TokenType;
use App\Models\EngineEvent;
use App\Models\Project;
use App\Models\Reaction;
use App\Models\Story;
use App\Services\Tokens\TokenService;
use App\States\Story\Shared;
use App\Support\Links;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Trois histoires partagées, et pas une réaction.
 *
 * C'est le silence qui tue le projet. Le narrateur a raconté trois fois, il a
 * accepté trois fois de partager, et rien ne lui revient — il en conclut,
 * raisonnablement, que personne n'écoute.
 *
 * On s'adresse à l'Initiateur·rice plutôt qu'à toute la famille : c'est elle
 * qui porte le projet, et un cœur d'elle vaut mieux qu'un rappel collectif que
 * chacun croira destiné à un autre. Une fois par mois, pas plus.
 */
final class ThreeStoriesNoReaction extends BaseRule
{
    public function __construct(private readonly TokenService $tokens) {}

    public function id(): EngineRuleId
    {
        return EngineRuleId::ThreeStoriesNoReaction;
    }

    public function audience(Occurrence $occurrence): EngineAudience
    {
        return EngineAudience::Initiator;
    }

    public function detect(CarbonImmutable $now): Collection
    {
        $needed = (int) config('product.engine.no_reaction_story_count');

        return Project::query()
            ->with(['owner', 'primaryNarrator'])
            ->whereHas('stories', fn ($query) => $query->where('state', Shared::$name))
            ->get()
            ->filter(function (Project $project) use ($needed): bool {
                $recent = $project->stories()
                    ->where('state', Shared::$name)
                    ->orderByDesc('shared_at')
                    ->limit($needed)
                    ->get();

                if ($recent->count() < $needed) {
                    return false;
                }

                // Une seule réaction sur l'une des trois suffit à rompre le
                // silence : ce qu'on détecte, c'est l'absence totale.
                return ! Reaction::query()
                    ->whereIn('story_id', $recent->pluck('id'))
                    ->exists();
            })
            ->map(fn (Project $project): Occurrence => new Occurrence(
                project: $project,
                key: 'no-reaction',
                // Le numéro de suggestion, et non un compteur d'histoires :
                // trois histoires restent trois le mois suivant, et la clé
                // ne changerait pas.
                attempt: $this->firedFor($project) + 1,
            ))
            ->values();
    }

    public function isCapped(Occurrence $occurrence): bool
    {
        if (InitiatorLoad::isSaturated($occurrence->project)) {
            return true;
        }

        $days = (int) config('product.engine.react_suggestion_min_interval_days');

        return $this->firedFor($occurrence->project, CarbonImmutable::now()->subDays($days)) >= 1;
    }

    public function fire(Occurrence $occurrence): array
    {
        $project = $occurrence->project;
        $count = (int) config('product.engine.no_reaction_story_count');

        $issued = $this->tokens->issue(
            TokenType::Action,
            $project,
            OneTapRegistry::scopeFor(ReactHeart::name()),
            now()->addDays(30),
        );

        return $this->tell(
            $project->owner,
            $occurrence,
            'react_suggestion',
            [
                // Sans narrateur principal, le message dit simplement
                // « votre proche » : la phrase reste lisible.
                'narrator' => (string) $project->primaryNarrator()->first()?->first_name,
                'count' => (string) $count,
            ],
            [[
                'label' => __('notifications.engine.react_suggestion.button'),
                'url' => Links::action($issued->plain),
            ]],
        );
    }

    public function resumed(EngineEvent $event, CarbonImmutable $now): ?bool
    {
        $reacted = Reaction::query()
            ->whereIn('story_id', Story::query()
                ->where('project_id', $event->project_id)
                ->where('state', Shared::$name)
                ->pluck('id'))
            ->where('created_at', '>=', $event->fired_at)
            ->exists();

        if ($reacted) {
            return true;
        }

        return $event->fired_at->lte($now->subDays(14)) ? false : null;
    }
}
