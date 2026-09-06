<?php

declare(strict_types=1);

namespace App\Books;

use App\Models\Book;
use App\Models\BookChapter;
use Illuminate\Support\Str;

/**
 * Les chapitres d'un livre, prêts pour l'écran.
 *
 * L'extrait fait cent vingt caractères : assez pour reconnaître l'histoire,
 * trop peu pour la lire. La page sert à **choisir et ordonner**, pas à
 * relire — la relecture se fait sur le bon à tirer, en pleine page, comme le
 * livre sera lu.
 */
final class SelectedChaptersPresenter
{
    private const EXCERPT = 120;

    /**
     * @return list<array<string, mixed>>
     */
    public static function forBook(Book $book): array
    {
        $book->loadMissing('chapters.story.question');

        /** @var list<array<string, mixed>> $rows */
        $rows = $book->chapters()->orderBy('position')->with(['story', 'qrToken'])->get()
            ->map(function (BookChapter $chapter): array {
                $story = $chapter->story;
                $text = SelectBookChapters::textOf($story);

                return [
                    'id' => $chapter->getKey(),
                    'storyId' => $story->getKey(),
                    'title' => $story->title,
                    'question' => $story->questionText(),
                    'recordedAt' => $story->recorded_at?->toIso8601String(),
                    'included' => (bool) $chapter->included,
                    'words' => $text === '' ? 0 : count(preg_split('/\s+/u', trim($text)) ?: []),
                    'excerpt' => Str::limit($text, self::EXCERPT),
                    'photos' => $story->getMedia($story::PHOTOS)->count(),
                    'hasQr' => $chapter->qr_token_id !== null,
                    // Actif, ou éteint par le narrateur : le libellé du lien
                    // en dépend, et proposer « désactiver » sur un code déjà
                    // mort ferait douter de ce qu'on a déjà fait.
                    'qrActive' => $chapter->qrToken !== null && $chapter->qrToken->revoked_at === null,
                ];
            })
            ->values()
            ->all();

        return $rows;
    }
}
