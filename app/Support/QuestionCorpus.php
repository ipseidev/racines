<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\QuestionCondition;
use App\Enums\QuestionTheme;
use App\Enums\QuestionTone;
use App\Enums\SensitiveTopic;

/**
 * Le corpus tel qu'il est écrit dans le dépôt, avant d'être en base.
 *
 * Un fichier PHP plutôt qu'une constante du seeder : il se relit comme un
 * document, il se diffe dans une revue, et `corpus:sync` le compare à la base
 * de production sans rien exécuter d'autre. Les étiquettes y sont des chaînes
 * et non des énumérations, pour que `problems()` puisse dire *laquelle* est
 * fausse au lieu de planter au chargement.
 *
 * @phpstan-type CorpusEntry array{
 *     slug: string,
 *     theme: string,
 *     difficulty: int,
 *     order: int,
 *     tone: string,
 *     conditions: list<string>,
 *     sensitive: list<string>,
 *     vous: string,
 *     tu: string,
 *     active?: bool,
 * }
 */
final readonly class QuestionCorpus
{
    public const DEFAULT_PATH = 'corpus/questions.php';

    /** @param list<CorpusEntry> $entries */
    private function __construct(private array $entries) {}

    public static function default(): self
    {
        return self::fromFile(database_path(self::DEFAULT_PATH));
    }

    public static function fromFile(string $path): self
    {
        /** @var list<CorpusEntry> $entries */
        $entries = require $path;

        return new self($entries);
    }

    /** @param list<CorpusEntry> $entries */
    public static function fromArray(array $entries): self
    {
        return new self($entries);
    }

    /** @return list<CorpusEntry> */
    public function entries(): array
    {
        return $this->entries;
    }

    /**
     * Tout ce qui empêche d'appliquer le fichier, une ligne par défaut,
     * préfixée du slug. Vide quand il est bon.
     *
     * @return list<string>
     */
    public function problems(): array
    {
        $problems = [];
        $seen = [];

        foreach ($this->entries as $index => $entry) {
            $slug = $entry['slug'];
            $at = $slug !== '' ? $slug : "entrée {$index}";

            if (preg_match('/^[a-z0-9]+(-[a-z0-9]+)*$/', $slug) !== 1) {
                $problems[] = "{$at} : slug invalide";
            }

            if (isset($seen[$slug])) {
                $problems[] = "{$at} : slug en double";
            }

            $seen[$slug] = true;

            if (QuestionTheme::tryFrom($entry['theme']) === null) {
                $problems[] = "{$at} : thème inconnu « {$entry['theme']} »";
            }

            if ($entry['difficulty'] < 1 || $entry['difficulty'] > 5) {
                $problems[] = "{$at} : difficulté hors de 1 à 5";
            }

            if (QuestionTone::tryFrom($entry['tone']) === null) {
                $problems[] = "{$at} : ton inconnu « {$entry['tone']} »";
            }

            foreach ($entry['conditions'] as $condition) {
                if (QuestionCondition::tryFrom($condition) === null) {
                    $problems[] = "{$at} : condition inconnue « {$condition} »";
                }
            }

            foreach ($entry['sensitive'] as $topic) {
                if (SensitiveTopic::tryFrom($topic) === null) {
                    $problems[] = "{$at} : sujet sensible inconnu « {$topic} »";
                }
            }

            foreach (['vous', 'tu'] as $form) {
                if (trim($entry[$form]) === '') {
                    $problems[] = "{$at} : texte « {$form} » vide";
                }

                foreach (QuestionWording::problems($entry[$form]) as $problem) {
                    $problems[] = "{$at} ({$form}) : {$problem}";
                }
            }
        }

        return $problems;
    }
}
