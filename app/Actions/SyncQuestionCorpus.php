<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Question;
use App\Support\QuestionCorpus;
use App\Support\QuestionWording;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Met la table `questions` d'accord avec le fichier du corpus.
 *
 * Trois règles, qui sont ce qui rend la commande sûre à lancer en production :
 *
 * 1. **Jamais de suppression.** Une question absente du fichier est laissée
 *    telle quelle : en production elle porte peut-être des histoires, en dev
 *    ce sont les questions de démonstration. Pour retirer une question, le
 *    fichier le dit (`'active' => false`).
 * 2. **Le slug est l'identité.** On met à jour par slug, jamais par position.
 * 3. **Un fichier invalide n'écrit rien.** Tout passe dans une transaction,
 *    après validation.
 *
 * Reformuler une question ne change pas les histoires déjà enregistrées :
 * elles ont photographié leur intitulé (`stories.question_text`).
 *
 * @phpstan-import-type CorpusEntry from QuestionCorpus
 *
 * @phpstan-type CorpusPlan array{
 *     added: list<string>,
 *     changed: array<string, list<string>>,
 *     unchanged: int,
 *     outside: list<string>,
 * }
 */
final class SyncQuestionCorpus
{
    /** @return CorpusPlan */
    public function plan(QuestionCorpus $corpus): array
    {
        $existing = Question::query()->get()->keyBy('slug');
        $plan = ['added' => [], 'changed' => [], 'unchanged' => 0, 'outside' => []];
        $inFile = [];

        foreach ($corpus->entries() as $entry) {
            $inFile[$entry['slug']] = true;
            $question = $existing->get($entry['slug']);

            if (! $question instanceof Question) {
                $plan['added'][] = $entry['slug'];

                continue;
            }

            $fields = $this->differences($question, $entry);

            if ($fields === []) {
                $plan['unchanged']++;
            } else {
                $plan['changed'][$entry['slug']] = $fields;
            }
        }

        foreach ($existing as $question) {
            if (! isset($inFile[$question->slug])) {
                $plan['outside'][] = $question->slug;
            }
        }

        return $plan;
    }

    /** @return CorpusPlan */
    public function apply(QuestionCorpus $corpus): array
    {
        $problems = $corpus->problems();

        if ($problems !== []) {
            throw new InvalidArgumentException("Corpus invalide :\n".implode("\n", $problems));
        }

        $plan = $this->plan($corpus);

        DB::transaction(function () use ($corpus): void {
            foreach ($corpus->entries() as $entry) {
                Question::query()->updateOrCreate(['slug' => $entry['slug']], $this->attributes($entry));
            }
        });

        return $plan;
    }

    /**
     * @param  CorpusEntry  $entry
     * @return array<string, mixed>
     */
    private function attributes(array $entry): array
    {
        return [
            'text' => $entry['vous'],
            'text_tu' => $entry['tu'],
            'theme' => $entry['theme'],
            'difficulty' => $entry['difficulty'],
            'order_hint' => $entry['order'],
            'tone' => $entry['tone'],
            'conditions' => $entry['conditions'],
            'sensitive_topics' => $entry['sensitive'],
            'is_active' => $entry['active'] ?? true,
            'locale' => 'fr',
        ];
    }

    /**
     * Les champs qui changeraient, sous leur nom du fichier.
     *
     * @param  CorpusEntry  $entry
     * @return list<string>
     */
    private function differences(Question $question, array $entry): array
    {
        $current = [
            'vous' => $question->text,
            'tu' => $question->text_tu,
            'theme' => $question->theme->value,
            'difficulty' => $question->difficulty,
            'order' => $question->order_hint,
            'tone' => $question->tone?->value,
            'conditions' => $question->conditions,
            'sensitive' => $question->sensitive_topics,
            'active' => $question->is_active,
        ];

        $wanted = [
            'vous' => $entry['vous'],
            'tu' => $entry['tu'],
            'theme' => $entry['theme'],
            'difficulty' => $entry['difficulty'],
            'order' => $entry['order'],
            'tone' => $entry['tone'],
            'conditions' => $entry['conditions'],
            'sensitive' => $entry['sensitive'],
            'active' => $entry['active'] ?? true,
        ];

        $fields = array_keys(array_filter(
            $wanted,
            fn (mixed $value, string $field): bool => $current[$field] !== $value,
            ARRAY_FILTER_USE_BOTH,
        ));

        // Poser un marqueur à la place d'un point médian change le gabarit
        // sans changer ce qu'on lit. Le rapport doit le dire : c'est la
        // différence entre une reformulation et un simple accord.
        return array_map(
            fn (string $field): string => $field === 'vous'
                && QuestionWording::resolve($entry['vous'], null) === QuestionWording::resolve($question->text, null)
                    ? 'vous (marqueurs, même texte)'
                    : $field,
            $fields,
        );
    }
}
