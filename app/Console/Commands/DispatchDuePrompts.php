<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\IssueRecordToken;
use App\Actions\PickNextQuestion;
use App\Actions\ProposeStory;
use App\Actions\ScheduleNextPrompt;
use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Notifications\CorpusExhaustedNotification;
use App\Notifications\PromptNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Attribute\AsCommand;
use Throwable;

/**
 * Envoie les questions dues.
 *
 * Tourne toutes les cinq minutes : un créneau de 9 h ne doit pas devenir 9 h 55
 * parce qu'un projet a mis du temps. Chaque projet est traité dans sa propre
 * transaction, pour qu'un échec chez l'un ne prive pas les autres de leur
 * question de la semaine.
 *
 * L'idempotence ne repose pas sur la chance : la clé de déduplication du
 * message porte l'identifiant de l'histoire, donc deux exécutions dans la même
 * minute n'envoient qu'un SMS.
 */
#[AsCommand(name: 'prompts:dispatch-due', description: 'Envoie les questions de la semaine dues')]
final class DispatchDuePrompts extends Command
{
    /** @var string */
    protected $signature = 'prompts:dispatch-due';

    /** @var string */
    protected $description = 'Envoie les questions de la semaine dues';

    public function handle(
        PickNextQuestion $pickQuestion,
        ProposeStory $proposeStory,
        IssueRecordToken $issueToken,
        ScheduleNextPrompt $schedule,
    ): int {
        $due = Project::query()
            ->where('status', ProjectStatus::Active->value)
            ->whereNotNull('next_prompt_at')
            ->where('next_prompt_at', '<=', now())
            ->get();

        $sent = 0;

        foreach ($due as $project) {
            try {
                $sent += $this->dispatchFor($project, $pickQuestion, $proposeStory, $issueToken, $schedule) ? 1 : 0;
            } catch (Throwable $exception) {
                // Un projet en échec ne prive pas les autres de leur question.
                Log::error('prompt.dispatch_failed', [
                    'project_id' => $project->id,
                    'reason' => $exception->getMessage(),
                ]);
            }
        }

        $this->components->info("{$sent} question(s) envoyée(s) sur {$due->count()} projet(s) dus.");

        return self::SUCCESS;
    }

    private function dispatchFor(
        Project $project,
        PickNextQuestion $pickQuestion,
        ProposeStory $proposeStory,
        IssueRecordToken $issueToken,
        ScheduleNextPrompt $schedule,
    ): bool {
        $narrator = $project->primaryNarrator()->first();

        if ($narrator === null) {
            Log::warning('prompt.no_narrator', ['project_id' => $project->id]);

            return false;
        }

        $question = $pickQuestion->handle($project);

        if ($question === null) {
            // Corpus épuisé : on le dit une fois à l'Initiateur·rice et on
            // arrête de planifier, plutôt que de tourner à vide chaque semaine.
            $this->reportExhaustedCorpus($project);

            return false;
        }

        return DB::transaction(function () use ($project, $narrator, $question, $proposeStory, $issueToken, $schedule): bool {
            $story = $proposeStory->handle($project, $question);
            $issued = $issueToken->handle($story);

            $narrator->notify(new PromptNotification($story, $issued->plain));

            $schedule->apply($project);

            Log::info('prompt.sent', [
                'project_id' => $project->id,
                'story_id' => $story->id,
                'question_slug' => $question->slug,
                'channel' => $narrator->preferred_channel->value,
            ]);

            return true;
        });
    }

    private function reportExhaustedCorpus(Project $project): void
    {
        $project->next_prompt_at = null;
        $project->save();

        $project->owner->notify(new CorpusExhaustedNotification($project));

        Log::info('prompt.corpus_exhausted', ['project_id' => $project->id]);
    }
}
