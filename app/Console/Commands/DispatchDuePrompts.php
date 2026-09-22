<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\IssueRecordToken;
use App\Actions\PickNextQuestion;
use App\Actions\ProposeStory;
use App\Actions\ScheduleNextPrompt;
use App\Enums\ProjectStatus;
use App\Enums\TokenType;
use App\Models\Narrator;
use App\Models\Project;
use App\Models\Story;
use App\Notifications\CorpusExhaustedNotification;
use App\Notifications\PromptNotification;
use App\States\Story\Proposed;
use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
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

        /*
         * Une question écrite par la famille passe **avant** le corpus.
         *
         * Elle devient une histoire PROPOSÉE dès qu'on l'écrit, et l'envoi
         * hebdomadaire ne la regardait pas : il piochait dans le corpus et
         * proposait une histoire de plus. Résultat, une question posée à la
         * main pouvait n'être **jamais envoyée** — elle s'affichait dans
         * l'espace, ce qui donnait toutes les raisons de croire qu'elle
         * partirait —, et deux histoires proposées s'empilaient au passage.
         *
         * Le moteur reste le moteur : il choisit dans le corpus quand
         * personne n'a rien demandé. Mais un choix délibéré de la famille
         * passe devant un séquencement automatique, comme l'ordre de la file
         * passe devant la règle 5 (décision T-63).
         */
        $pending = $this->pendingUnsentStory($project);
        $question = $pickQuestion->handle($project);

        /*
         * Le rang décide, pas la nature (T-255).
         *
         * Une question écrite par la famille passait **toujours** devant le
         * corpus, sans recours : deux natures, deux échelles, aucun ordre
         * commun. `queue_order` la place dans la même échelle que la file du
         * corpus, dont la première occupe le rang 0 après un réordonnancement.
         * On compare donc deux entiers.
         *
         * À égalité, la famille passe devant : un choix délibéré vaut mieux
         * qu'un séquencement automatique (T-63). Et sans corpus disponible,
         * la question de la famille part quoi qu'il arrive.
         */
        $rangCorpus = $question === null
            ? null
            : (int) ($project->questionSettings()
                ->where('question_id', $question->id)
                ->value('custom_order') ?? PHP_INT_MAX);

        if ($pending instanceof Story
            && ($rangCorpus === null || (int) ($pending->queue_order ?? 0) <= $rangCorpus)) {
            return $this->send($project, $narrator, $pending, $issueToken, $schedule, 'custom');
        }

        if ($question === null) {
            // Corpus épuisé : on le dit une fois à l'Initiateur·rice et on
            // arrête de planifier, plutôt que de tourner à vide chaque semaine.
            $this->reportExhaustedCorpus($project);

            return false;
        }

        return DB::transaction(function () use ($project, $narrator, $question, $proposeStory, $issueToken, $schedule): bool {
            $story = $proposeStory->handle($project, $question);

            return $this->send($project, $narrator, $story, $issueToken, $schedule, $question->slug);
        });
    }

    /**
     * L'histoire proposée dont le lien n'est jamais parti.
     *
     * Le signal est **l'absence de jeton d'enregistrement** : un lien émis
     * est un lien remis, que ce soit par l'envoi hebdomadaire ou par
     * « Envoyer le lien » depuis l'espace. La table des histoires ne porte
     * pas de date d'envoi, et en ajouter une pour ce seul besoin dupliquerait
     * ce que les jetons savent déjà.
     *
     * Triée par `queue_order`, qui est son rang dans la file commune, puis
     * par `sequence` pour départager deux rangs égaux : deux questions
     * écrites à la suite partent alors dans l'ordre où elles ont été
     * écrites.
     */
    private function pendingUnsentStory(Project $project): ?Story
    {
        $story = $project->stories()
            ->where('state', Proposed::$name)
            ->whereNotExists(fn (Builder $query) => $query
                ->selectRaw('1')
                ->from('access_tokens')
                /*
                 * `stories.id::text` : `access_tokens.subject_id` est une
                 * **chaîne** — le sujet peut être une histoire, un projet ou
                 * un narrateur, et la colonne les porte tous —, alors que
                 * `stories.id` est un `uuid`. Postgres refuse de comparer les
                 * deux sans conversion explicite, et `whereColumn` n'en pose
                 * aucune.
                 */
                ->whereRaw('access_tokens.subject_id = stories.id::text')
                ->where('access_tokens.subject_type', 'story')
                ->where('access_tokens.type', TokenType::Record->value))
            ->orderBy('queue_order')
            ->orderBy('sequence')
            ->first();

        return $story instanceof Story ? $story : null;
    }

    /** Le lien part, la narratrice est prévenue, le prochain envoi est posé. */
    private function send(
        Project $project,
        Narrator $narrator,
        Story $story,
        IssueRecordToken $issueToken,
        ScheduleNextPrompt $schedule,
        string $origin,
    ): bool {
        $issued = $issueToken->handle($story);

        $narrator->notify(new PromptNotification($story, $issued->plain));

        $schedule->apply($project);

        Log::info('prompt.sent', [
            'project_id' => $project->id,
            'story_id' => $story->id,
            'question_slug' => $origin,
            'channel' => $narrator->preferred_channel->value,
        ]);

        return true;
    }

    private function reportExhaustedCorpus(Project $project): void
    {
        $project->next_prompt_at = null;
        $project->save();

        $project->owner->notify(new CorpusExhaustedNotification($project));

        Log::info('prompt.corpus_exhausted', ['project_id' => $project->id]);
    }
}
