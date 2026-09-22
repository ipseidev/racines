<?php

declare(strict_types=1);

namespace App\Support;

use App\Actions\PickNextQuestion;
use App\Actions\ScheduleNextPrompt;
use App\Enums\TokenType;
use App\Models\Project;
use App\Models\Question;
use App\Models\Story;
use App\States\Story\Proposed;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Arr;

/**
 * La file d'envoi : ce qui partira, dans l'ordre, avec les dates.
 *
 * Deux natures s'y mêlent — une question du fonds éditorialisé, qui attend son
 * tour dans `project_question_settings.custom_order`, et une question écrite
 * par la famille, qui est **déjà** une histoire PROPOSÉE et porte son rang
 * dans `stories.queue_order`. Les deux vivent dans la même échelle, et c'est
 * ce qui permet à l'une de passer devant l'autre.
 *
 * Écrit ici et non dans un contrôleur parce que **deux pages la lisent** : la
 * page des questions, qui la réordonne, et l'accueil, qui en montre le début
 * dans sa frise. Deux requêtes écrites séparément auraient divergé au premier
 * changement de règle — et c'est toujours la copie oubliée qui ment.
 */
final readonly class QuestionQueue
{
    /**
     * Le rang de ce que personne n'a classé à la main.
     *
     * Très au-delà de tout rang posé par un humain : une file de soixante-cinq
     * questions n'atteindra jamais mille, et l'ordre du moteur reprend donc la
     * main derrière les choix explicites.
     */
    public const UNRANKED = 1_000;

    public function __construct(
        private PickNextQuestion $picker,
        private ScheduleNextPrompt $schedule,
    ) {}

    /**
     * La file, triée, les `$dated` premières portant leur date d'envoi.
     *
     * Les dates viennent de `ScheduleNextPrompt`, qui **est** le
     * planificateur : cadence, créneau, fuseau et pause compris. Les
     * recalculer ici produirait une seconde vérité.
     *
     * Jamais au-delà de `$dated` : une pause, un changement de rythme ou une
     * question sautée décalent tout ce qui suit, et une date affichée sur la
     * trentième serait une promesse qu'on ne tient pas.
     *
     * @return list<array<string, mixed>>
     */
    public function forProject(Project $project, int $dated = 8): array
    {
        $entries = [];

        foreach ($this->pendingStories($project) as $story) {
            $entries[] = [
                'kind' => 'story',
                'id' => $story->id,
                'text' => (string) $story->custom_question_text,
                'theme' => null,
                'themeLabel' => null,
                // Qui l'a posée : un proche nommé, ou l'Initiateur·rice quand
                // la colonne est nulle (R-1, dossier v3.1).
                'askedBy' => $story->proposedBy?->display_name,
                'photos' => count(PhotoPresenter::promptsForStory($story)),
                'rank' => (int) ($story->queue_order ?? 0),
                'tie' => 0,
            ];
        }

        /*
         * Le rang **stocké**, et non la position dans la file du fonds.
         *
         * Les deux se ressemblent et ne sont pas la même chose : après un
         * réordonnancement, une question du fonds porte `custom_order = 1`
         * tout en étant première de sa propre file — sa position y est donc 0.
         * Comparer cette position au `queue_order` d'une histoire créait une
         * égalité, et l'égalité redonnait la main à la famille : une question
         * du fonds remontée en tête retombait au second rang.
         */
        $ranks = $project->questionSettings()
            ->whereNotNull('custom_order')
            ->pluck('custom_order', 'question_id');

        foreach ($this->picker->queue($project)->values() as $position => $question) {
            /** @var Question $question */
            $entries[] = [
                'kind' => 'question',
                'id' => $question->id,
                'text' => $question->text,
                'theme' => $question->theme->value,
                'themeLabel' => Options::label($question->theme),
                'askedBy' => null,
                'photos' => 0,
                'rank' => (int) ($ranks[$question->id] ?? self::UNRANKED + $position),
                'tie' => 1,
            ];
        }

        // À égalité, la question de la famille passe devant : un choix
        // délibéré vaut mieux qu'un séquencement automatique (T-63).
        usort(
            $entries,
            fn (array $a, array $b): int => [$a['rank'], $a['tie']] <=> [$b['rank'], $b['tie']],
        );

        $date = $project->next_prompt_at;

        foreach ($entries as $position => $entry) {
            $entries[$position]['sendAt'] = $position < $dated && $date !== null
                ? $date->toIso8601String()
                : null;

            if ($position < $dated && $date !== null) {
                $date = $this->schedule->handle($project, $date);
            }
        }

        return array_map(
            fn (array $entry): array => Arr::except($entry, ['rank', 'tie']),
            $entries,
        );
    }

    /**
     * Les questions écrites par la famille, pas encore parties.
     *
     * Le signal est **l'absence de jeton d'enregistrement** : un lien émis est
     * un lien remis, que ce soit par l'envoi hebdomadaire ou par « Envoyer le
     * lien ». La table des histoires ne porte pas de date d'envoi, et en
     * ajouter une pour ce seul besoin dupliquerait ce que les jetons savent.
     *
     * `stories.id::text` parce que `access_tokens.subject_id` est une chaîne —
     * le sujet peut être une histoire, un projet ou un narrateur — quand
     * `stories.id` est un `uuid`, et que Postgres refuse de les comparer sans
     * conversion.
     *
     * @return Collection<int, Story>
     */
    public function pendingStories(Project $project): Collection
    {
        return $project->stories()
            ->with('proposedBy')
            ->where('state', Proposed::$name)
            ->whereNotNull('custom_question_text')
            ->whereNotExists(fn (Builder $query) => $query
                ->selectRaw('1')
                ->from('access_tokens')
                ->whereRaw('access_tokens.subject_id = stories.id::text')
                ->where('access_tokens.subject_type', 'story')
                ->where('access_tokens.type', TokenType::Record->value))
            ->orderBy('queue_order')
            ->orderBy('sequence')
            ->get();
    }
}
