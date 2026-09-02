<?php

declare(strict_types=1);

namespace App\Services\Llm;

use App\Models\Transcript;

/**
 * Mise au propre simulée : ponctuation naïve, titre tiré des premiers mots.
 *
 * Elle ne prétend pas imiter le modèle. Elle prétend seulement produire un
 * texte différent du verbatim, avec un titre et des thèmes, pour que le
 * pipeline et les écrans soient éprouvables sans appeler le réseau.
 */
final class FakeStoryRenderer implements StoryRenderer
{
    private bool $refuses = false;

    public function refusing(): self
    {
        $this->refuses = true;

        return $this;
    }

    public function render(Transcript $verbatim, RenderingContext $context): FluideResult
    {
        if ($this->refuses) {
            return FluideResult::refused(['provider' => 'fake', 'category' => 'test']);
        }

        $text = self::capitalizeSentences(self::stripFillers($verbatim->text));
        $words = preg_split('/\s+/u', trim($text)) ?: [];

        return new FluideResult(
            title: mb_substr(implode(' ', array_slice($words, 0, 6)), 0, 60),
            text: $text,
            themes: array_slice($context->themes, 0, 1),
            properNouns: array_values($context->lexicon),
            sensitiveFlags: [],
            metadata: ['provider' => 'fake', 'model' => 'fake-renderer'],
        );
    }

    private static function stripFillers(string $text): string
    {
        return (string) preg_replace('/\b(euh|ben|bah|hein|voilà quoi)\b[\s,]*/iu', '', $text);
    }

    private static function capitalizeSentences(string $text): string
    {
        $text = trim((string) preg_replace('/\s+/u', ' ', $text));

        return (string) preg_replace_callback(
            '/(^|[.!?]\s+)(\p{Ll})/u',
            fn (array $m): string => $m[1].mb_strtoupper($m[2]),
            $text,
        );
    }
}
