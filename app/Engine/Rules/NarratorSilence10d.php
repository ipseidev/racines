<?php

declare(strict_types=1);

namespace App\Engine\Rules;

use App\Actions\IssueRecordToken;
use App\Actions\PickNextQuestion;
use App\Actions\ProposeStory;
use App\Engine\BaseRule;
use App\Engine\Occurrence;
use App\Enums\EngineRuleId;
use App\Enums\ProjectStatus;
use App\Models\EngineEvent;
use App\Models\Project;
use App\Models\Story;
use App\Support\Links;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Dix jours sans un mot.
 *
 * On ne relance pas : on **change de question**. Une question plus légère,
 * difficulté deux au plus, parce qu'un silence de dix jours veut souvent dire
 * que la précédente était trop lourde — ou tombée un mauvais jour.
 *
 * Le message le dit franchement : « une minute suffit, et vous pouvez tout
 * aussi bien la laisser de côté ». Le narrateur n'a rien promis.
 */
final class NarratorSilence10d extends BaseRule
{
    public function __construct(
        private readonly PickNextQuestion $questions,
        private readonly ProposeStory $stories,
        private readonly IssueRecordToken $tokens,
    ) {}

    public function id(): EngineRuleId
    {
        return EngineRuleId::NarratorSilence10d;
    }

    public function detect(CarbonImmutable $now): Collection
    {
        $days = (int) config('product.engine.silence_light_question_days');

        return Project::query()
            ->with('primaryNarrator')
            ->where('status', ProjectStatus::Active->value)
            // Le projet a démarré : une invitation jamais acceptée relève
            // d'une autre règle.
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
                key: 'silence',
                attempt: $this->firedFor($project) + 1,
            ))
            ->values();
    }

    public function isCapped(Occurrence $occurrence): bool
    {
        $days = (int) config('product.engine.silence_light_question_days');

        // Une question plus légère tous les dix jours au plus : au-delà, on
        // ne propose plus, on harcèle.
        return $this->firedFor($occurrence->project, CarbonImmutable::now()->subDays($days)) >= 1;
    }

    public function fire(Occurrence $occurrence): array
    {
        $narrator = $occurrence->narrator;

        if ($narrator === null) {
            return ['skipped' => 'no_narrator'];
        }

        $question = $this->questions->handle($occurrence->project, easier: true);

        if ($question === null) {
            return ['skipped' => 'corpus_exhausted'];
        }

        $story = $this->stories->handle($occurrence->project, $question);
        $issued = $this->tokens->handle($story);

        return [
            ...$this->tell(
                $narrator,
                $occurrence,
                'lighter_question',
                [],
                [[
                    'label' => __('notifications.engine.lighter_question.button'),
                    'url' => Links::record($issued->plain),
                ]],
            ),
            'story_id' => $story->id,
            'question_id' => $question->id,
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

        return $event->fired_at->lte($now->subDays(14)) ? false : null;
    }
}
