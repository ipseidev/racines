<?php

declare(strict_types=1);

namespace App\Books;

use App\Enums\ShareDecision;
use App\Enums\TranscriptKind;
use App\Models\Project;
use App\Models\Story;
use App\States\Story\InBook;
use App\States\Story\Shared;
use App\States\Story\Validated;

/**
 * Mesure la matière d'un projet selon R-6.
 *
 * **Jamais un compte d'histoires**, et c'est l'interdit central du bloc. Une
 * famille qui a raconté huit longues histoires a plus de matière qu'une
 * famille qui en a raconté vingt-cinq de deux minutes : un seuil au nombre
 * ferait attendre la première et déclencherait trop tôt pour la seconde.
 * « ~25 histoires » reste un repère marketing, jamais un critère.
 *
 * Seules les histoires **validées, partagées ou déjà dans un livre** comptent.
 * Une histoire masquée, archivée ou à la corbeille ne peut pas entrer dans un
 * livre — elle ne peut donc pas servir à décider qu'un livre est possible.
 */
final class ComputeBookReadiness
{
    /**
     * Les états dont la matière compte.
     *
     * @return list<string>
     */
    public static function countableStates(): array
    {
        return [Validated::$name, Shared::$name, InBook::$name];
    }

    public function handle(Project $project): BookReadiness
    {
        $stories = $project->stories()
            ->whereIn('state', self::countableStates())
            ->with(['question', 'transcripts', 'recordings'])
            ->get();

        $words = 0;
        $seconds = 0.0;
        $themes = [];
        $photos = 0;
        $sensitiveUndecided = 0;

        foreach ($stories as $story) {
            $words += self::wordsOf($story);
            $seconds += self::secondsOf($story);
            $photos += $story->getMedia(Story::PHOTOS)->count();

            $theme = $story->question?->theme;

            if ($theme !== null) {
                // Les thèmes **distincts** : un livre qui ne parle que
                // d'enfance n'est pas un livre de vie.
                $themes[$theme->value] = true;
            }

            if (self::isSensitiveUndecided($story)) {
                $sensitiveUndecided++;
            }
        }

        return new BookReadiness(
            words: $words,
            audioMinutes: $seconds / 60,
            estimatedPages: self::estimatePages($words, $photos, $stories->count()),
            themes: count($themes),
            chapters: $stories->count(),
            sensitiveUndecided: $sensitiveUndecided,
        );
    }

    /**
     * Les pages estimées au gabarit.
     *
     * Une estimation et non une mesure : le nombre réel sort du rendu PDF, qui
     * coûte cinq minutes de Chromium. La jauge doit répondre tout de suite, et
     * une estimation à quelques pages près suffit à décider si un livre est
     * possible.
     */
    public static function estimatePages(int $words, int $photos, int $chapters): int
    {
        $perPage = max(1, (int) config('product.book_ready.words_per_page'));

        return (int) floor($words / $perPage + $photos * 0.5 + $chapters * 0.5);
    }

    /**
     * Les mots du texte qui sera imprimé.
     *
     * Le rendu lisible, pas le mot à mot : c'est le texte mis au propre qui
     * va dans le livre, et il est plus court que la parole.
     */
    private static function wordsOf(Story $story): int
    {
        $text = $story->transcripts
            ->where('is_current', true)
            ->sortByDesc(fn ($transcript): int => match ($transcript->kind) {
                TranscriptKind::Edited => 3,
                TranscriptKind::Fluide => 2,
                TranscriptKind::Verbatim => 1,
            })
            ->first()->text ?? $story->written_answer ?? '';

        return $text === '' ? 0 : count(preg_split('/\s+/', trim($text)) ?: []);
    }

    private static function secondsOf(Story $story): float
    {
        return (float) $story->recordings
            ->whereNotNull('confirmed_at')
            ->sum(fn ($recording): float => (float) ($recording->duration_seconds ?? 0));
    }

    /**
     * L'histoire porte-t-elle un sujet sensible non tranché ?
     *
     * Les marqueurs viennent des métadonnées du rendu Fluide, où le bloc 06
     * les écrit. « Tranché » veut dire une décision de partage explicite :
     * `decide_later` n'en est pas une, c'est le contraire.
     */
    private static function isSensitiveUndecided(Story $story): bool
    {
        $flags = $story->transcripts
            ->where('kind', TranscriptKind::Fluide)
            ->pluck('metadata')
            ->flatMap(fn (?array $metadata): array => (array) ($metadata['sensitive_flags'] ?? []))
            ->filter()
            ->all();

        if ($flags === []) {
            return false;
        }

        return ! in_array(
            $story->share_decision,
            [ShareDecision::Share, ShareDecision::KeepPrivate],
            true,
        );
    }
}
