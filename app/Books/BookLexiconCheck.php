<?php

declare(strict_types=1);

namespace App\Books;

use App\Enums\TranscriptKind;
use App\Models\Book;
use App\Models\LexiconEntry;
use App\Models\Transcript;

/**
 * Les noms propres du livre que le lexique ne connaît pas encore.
 *
 * Ce contrôle existe pour une raison très concrète : la transcription se
 * trompe sur les noms. « Kerhostin » devient « Ker Rostin », un grand-oncle
 * « Ambroise » devient « Ambroisie », et **cela s'imprime**. Une faute sur le
 * nom d'un lieu ou d'un aïeul est la coquille que la famille remarquera en
 * premier, et la seule qu'elle ne pardonnera pas.
 *
 * Les noms viennent des **métadonnées du rendu Fluide**, où le bloc 06 les
 * dépose comme suggestions et non comme vérités : c'est la famille qui décide
 * de la graphie de ses noms. Le contrôle ne corrige donc rien — il montre la
 * liste, et demande qu'on la regarde avant d'approuver.
 *
 * La comparaison ignore la casse et les accents : « kerhostin » et
 * « Kerhostin » sont le même mot pour qui l'a déjà vérifié une fois.
 */
final readonly class BookLexiconCheck
{
    /**
     * @return list<string>
     */
    public function handle(Book $book): array
    {
        $known = LexiconEntry::query()
            ->where('project_id', $book->project_id)
            ->pluck('term')
            ->map(self::normalise(...))
            ->all();

        $found = [];

        foreach ($book->chapters()->where('included', true)->with('story.transcripts')->get() as $chapter) {
            $story = $chapter->story;

            $transcript = $story->transcripts()
                ->ofKind(TranscriptKind::Fluide)
                ->current()
                ->first();

            /** @var array<string, mixed> $metadata */
            $metadata = $transcript instanceof Transcript ? ($transcript->metadata ?? []) : [];
            /** @var list<mixed> $nouns */
            $nouns = is_array($metadata['proper_nouns'] ?? null) ? $metadata['proper_nouns'] : [];

            foreach ($nouns as $noun) {
                if (! is_string($noun) || trim($noun) === '') {
                    continue;
                }

                $key = self::normalise($noun);

                if (in_array($key, $known, true) || isset($found[$key])) {
                    continue;
                }

                $found[$key] = trim($noun);
            }
        }

        $values = array_values($found);
        sort($values, SORT_NATURAL | SORT_FLAG_CASE);

        return $values;
    }

    private static function normalise(string $term): string
    {
        $folded = iconv('UTF-8', 'ASCII//TRANSLIT', $term);

        return mb_strtolower(trim($folded === false ? $term : $folded));
    }
}
