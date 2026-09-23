<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\QuestionCondition;
use App\Enums\QuestionTheme;
use App\Models\Project;
use App\Models\Question;
use App\Support\QuestionProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Choisit la prochaine question à poser à un narrateur.
 *
 * Les six règles de l'annexe A, et leur raison d'être : une première question
 * intime fait raccrocher, une première question facile fait parler dix
 * minutes. L'ordre du corpus porte cette progression, et rien ici ne
 * l'outrepasse — sauf l'Initiateur·rice, qui connaît sa famille mieux que le
 * corpus.
 *
 * Le profil rempli après l'achat (`QuestionProfile`) retire ce qui ne
 * s'applique pas, avance les thèmes choisis et place les questions sur
 * l'acheteur. Sans profil, rien ne change.
 */
final class PickNextQuestion
{
    /**
     * Nombre d'histoires validées avant d'oser les questions intimes
     * (règle 5). Cinq semaines de confiance, puis « qu'aimeriez-vous que l'on
     * retienne de vous ? ».
     */
    private const VALIDATED_BEFORE_INTIMATE = 6;

    /** Seuil de la règle 5 : au-delà, la question est intime. */
    private const INTIMATE_DIFFICULTY = 4;

    /** Plafond de la règle 4 : ce qu'on propose quand quelqu'un s'essouffle. */
    private const EASIER_DIFFICULTY = 2;

    /**
     * Les questions sur l'acheteur : la première à la sixième question — le
     * narrateur doit d'abord sentir que le livre parle de lui —, puis une
     * toutes les huit, six au plus. Quatre à six sur cinquante-deux.
     */
    private const BUYER_FIRST_AT = 5;

    private const BUYER_SPACING = 8;

    private const BUYER_MAX = 6;

    /**
     * Ce que pèse un thème que la famille a choisi de mettre en avant : son
     * rang dans le corpus est multiplié par ce facteur. Il passe devant sans
     * faire disparaître les autres, dont le livre a besoin pour être prêt.
     */
    private const FAVORED_WEIGHT = 0.6;

    /**
     * @param  bool  $easier  Règle 4 : le moteur a détecté un silence et
     *                        demande une question plus douce.
     */
    public function handle(Project $project, bool $easier = false): ?Question
    {
        $state = $this->state($project);

        // Règle 3 avant tout le reste : une question avancée par
        // l'Initiateur·rice passe devant, y compris intime.
        //
        // La règle 5 protège du séquencement *automatique*, qui ne connaît
        // pas la famille ; elle n'a pas à contredire un choix délibéré de la
        // personne qui l'organise. Le narrateur, lui, garde le droit de ne pas
        // répondre — c'est là que vit sa souveraineté, pas dans le corpus.
        foreach ($this->advancedIds($project) as $id) {
            $advanced = $state['candidates']->firstWhere('id', $id);

            if ($advanced instanceof Question) {
                return $advanced;
            }
        }

        $validated = $project->stories()->whereState('state', ['validated', 'shared', 'in_book'])->count();

        return $this->choose($state, intimateAllowed: $validated >= self::VALIDATED_BEFORE_INTIMATE, easier: $easier);
    }

    /**
     * Thèmes couverts par au moins une histoire **validée** (règle 6, R-6).
     *
     * Une histoire enregistrée mais non validée ne couvre rien : le critère
     * book-ready compte ce que le narrateur a accepté de garder.
     *
     * @return list<string>
     */
    public function coveredThemes(Project $project): array
    {
        $themes = $project->stories()
            ->whereState('state', ['validated', 'shared', 'in_book'])
            ->whereNotNull('question_id')
            ->with('question')
            ->get()
            ->map(fn ($story): ?QuestionTheme => $story->question?->theme)
            ->filter()
            ->map(fn (QuestionTheme $theme): string => $theme->value)
            ->unique()
            ->sort()
            ->values()
            ->all();

        return array_values($themes);
    }

    public function coversEnoughThemes(Project $project): bool
    {
        return count($this->coveredThemes($project)) >= (int) config('product.book_ready.min_themes');
    }

    /**
     * Les prochaines questions, dans l'ordre où elles partiront : celles que
     * l'Initiateur·rice a avancées d'abord, puis le corpus du facile vers
     * l'intime. C'est ce que son espace affiche, et c'est aussi ce que
     * `handle()` lit, à une réserve près : la règle 5 (l'intime attend la
     * sixième histoire validée) et la règle 4 (plus doux sur demande du moteur)
     * s'appliquent au moment de l'envoi, pas à la liste.
     *
     * La file n'est pas triée à part : elle **rejoue** l'envoi, question après
     * question. Les questions sur l'acheteur tombent à des rangs, pas à des
     * places du corpus ; un tri les montrerait ailleurs que là où elles
     * partiront.
     *
     * @return Collection<int, Question>
     */
    public function queue(Project $project): Collection
    {
        $state = $this->state($project);
        $queue = [];

        foreach ($this->advancedIds($project) as $id) {
            $advanced = $state['candidates']->firstWhere('id', $id);

            if ($advanced instanceof Question) {
                $queue[] = $advanced;
                $state = $this->record($state, $advanced);
            }
        }

        while (($next = $this->choose($state, intimateAllowed: true, easier: false)) instanceof Question) {
            $queue[] = $next;
            $state = $this->record($state, $next);
        }

        return collect($queue);
    }

    /**
     * Où en est le projet : ce qui peut encore partir, dans l'ordre, et ce qui
     * est déjà parti.
     *
     * @return array{candidates: Collection<int, Question>, profile: QuestionProfile, sent: int, buyerSent: int, lastBuyerAt: int|null}
     */
    private function state(Project $project): array
    {
        $profile = QuestionProfile::of($project);
        $asked = $project->stories()->whereNotNull('question_id')->pluck('question_id');
        $excluded = $project->questionSettings()->where('excluded', true)->pluck('question_id');

        $candidates = Question::query()
            ->active()
            ->whereNotIn('id', $asked)
            ->whereNotIn('id', $excluded)
            ->get()
            ->filter(fn (Question $question): bool => $profile->allows($question))
            ->sortBy([
                fn (Question $a, Question $b): int => $this->rank($profile, $a) <=> $this->rank($profile, $b),
                fn (Question $a, Question $b): int => $a->slug <=> $b->slug,
            ])
            ->values();

        // Les rangs des questions sur l'acheteur déjà parties, pour tenir
        // l'espacement d'une semaine à l'autre.
        $buyerRanks = $project->stories()
            ->whereHas('question', fn (Builder $query): Builder => $query->whereJsonContains('conditions', QuestionCondition::AboutBuyer->value))
            ->pluck('sequence');

        return [
            'candidates' => $candidates,
            'profile' => $profile,
            'sent' => $project->stories()->count(),
            'buyerSent' => $buyerRanks->count(),
            'lastBuyerAt' => $buyerRanks->isEmpty() ? null : (int) $buyerRanks->max() - 1,
        ];
    }

    /**
     * La prochaine question du point de vue des règles, sans rien écrire.
     *
     * @param  array{candidates: Collection<int, Question>, profile: QuestionProfile, sent: int, buyerSent: int, lastBuyerAt: int|null}  $state
     */
    private function choose(array $state, bool $intimateAllowed, bool $easier): ?Question
    {
        $pool = $state['candidates']
            // Règle 5 : l'intime attend la sixième histoire validée.
            ->filter(fn (Question $question): bool => $intimateAllowed || $question->difficulty < self::INTIMATE_DIFFICULTY)
            // Règle 4 : plus doux, sur demande du moteur.
            ->filter(fn (Question $question): bool => ! $easier || $question->difficulty <= self::EASIER_DIFFICULTY);

        $profile = $state['profile'];

        if ($profile->talksAboutBuyer()) {
            $due = $state['sent'] >= self::BUYER_FIRST_AT
                && $state['buyerSent'] < self::BUYER_MAX
                && ($state['lastBuyerAt'] === null || $state['sent'] - $state['lastBuyerAt'] >= self::BUYER_SPACING);

            if ($due) {
                $buyer = $pool->first(fn (Question $question): bool => $profile->isAboutBuyer($question));

                if ($buyer instanceof Question) {
                    return $buyer;
                }
            }

            $pool = $pool->reject(fn (Question $question): bool => $profile->isAboutBuyer($question));
        }

        // Règles 1 et 2 : l'ordre du corpus, du facile vers l'intime. La
        // règle 1 n'est pas un cas particulier — la première question de
        // difficulté 1 est aussi la première du corpus.
        $next = $pool->first();

        return $next instanceof Question ? $next : null;
    }

    /**
     * @param  array{candidates: Collection<int, Question>, profile: QuestionProfile, sent: int, buyerSent: int, lastBuyerAt: int|null}  $state
     * @return array{candidates: Collection<int, Question>, profile: QuestionProfile, sent: int, buyerSent: int, lastBuyerAt: int|null}
     */
    private function record(array $state, Question $question): array
    {
        $isBuyer = $state['profile']->isAboutBuyer($question);

        return [
            'candidates' => $state['candidates']->reject(fn (Question $candidate): bool => $candidate->id === $question->id)->values(),
            'profile' => $state['profile'],
            'sent' => $state['sent'] + 1,
            'buyerSent' => $state['buyerSent'] + ($isBuyer ? 1 : 0),
            'lastBuyerAt' => $isBuyer ? $state['sent'] : $state['lastBuyerAt'],
        ];
    }

    private function rank(QuestionProfile $profile, Question $question): float
    {
        return $question->order_hint * ($profile->favors($question) ? self::FAVORED_WEIGHT : 1.0);
    }

    /**
     * Les identifiants avancés par l'Initiateur·rice, dans son ordre.
     *
     * @return list<string>
     */
    private function advancedIds(Project $project): array
    {
        $ids = $project->questionSettings()
            ->whereNotNull('custom_order')
            ->where('excluded', false)
            ->orderBy('custom_order')
            ->pluck('question_id')
            ->all();

        return array_values(array_map(static fn (mixed $id): string => (string) $id, $ids));
    }
}
