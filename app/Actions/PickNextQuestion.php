<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\QuestionTheme;
use App\Models\Project;
use App\Models\Question;
use Illuminate\Database\Eloquent\Builder;

/**
 * Choisit la prochaine question à poser à un narrateur.
 *
 * Les six règles de l'annexe A, et leur raison d'être : une première question
 * intime fait raccrocher, une première question facile fait parler dix
 * minutes. L'ordre du corpus porte cette progression, et rien ici ne
 * l'outrepasse — sauf l'Initiateur·rice, qui connaît sa famille mieux que le
 * corpus.
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
     * @param  bool  $easier  Règle 4 : le moteur a détecté un silence et
     *                        demande une question plus douce.
     */
    public function handle(Project $project, bool $easier = false): ?Question
    {
        $asked = $project->stories()->whereNotNull('question_id')->pluck('question_id');
        $validated = $project->stories()->whereState('state', ['validated', 'shared', 'in_book'])->count();

        $excluded = $project->questionSettings()->where('excluded', true)->pluck('question_id');

        $available = fn (): Builder => Question::query()
            ->active()
            ->whereNotIn('id', $asked)
            ->whereNotIn('id', $excluded);

        // Règle 3 avant tout le reste : une question avancée par
        // l'Initiateur·rice passe devant, y compris intime.
        //
        // La règle 5 protège du séquencement *automatique*, qui ne connaît
        // pas la famille ; elle n'a pas à contredire un choix délibéré de la
        // personne qui l'organise. Le narrateur, lui, garde le droit de ne pas
        // répondre — c'est là que vit sa souveraineté, pas dans le corpus.
        $advanced = $this->firstAdvanced($project, $available());

        if ($advanced instanceof Question) {
            return $advanced;
        }

        $base = $available();

        // Règle 5 : l'intime attend la sixième histoire validée.
        if ($validated < self::VALIDATED_BEFORE_INTIMATE) {
            $base->where('difficulty', '<', self::INTIMATE_DIFFICULTY);
        }

        // Règle 4 : plus doux, sur demande du moteur.
        if ($easier) {
            $base->where('difficulty', '<=', self::EASIER_DIFFICULTY);
        }

        // Règles 1 et 2 : l'ordre du corpus, du facile vers l'intime. La
        // règle 1 n'est pas un cas particulier — la première question de
        // difficulté 1 est aussi la première du corpus.
        return $base->orderBy('order_hint')->orderBy('slug')->first();
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
     * @param  Builder<Question>  $base
     */
    private function firstAdvanced(Project $project, Builder $base): ?Question
    {
        $ordered = $project->questionSettings()
            ->whereNotNull('custom_order')
            ->where('excluded', false)
            ->orderBy('custom_order')
            ->pluck('question_id')
            ->all();

        if ($ordered === []) {
            return null;
        }

        $candidates = (clone $base)->whereIn('id', $ordered)->get()->keyBy('id');

        foreach ($ordered as $id) {
            $question = $candidates->get($id);

            if ($question instanceof Question) {
                return $question;
            }
        }

        return null;
    }
}
