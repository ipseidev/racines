<?php

declare(strict_types=1);

namespace App\Books;

/**
 * L'état de la matière recueillie, mesuré selon R-6.
 *
 * Un objet de valeur et non un booléen : la jauge de l'espace affiche les
 * cinq mesures, et l'Initiateur·rice doit voir **ce qui manque** plutôt qu'un
 * feu rouge. « Il manque des thèmes » se comprend ; « pas encore prêt » fait
 * attendre sans savoir quoi faire.
 */
final readonly class BookReadiness
{
    public function __construct(
        public int $words,
        public float $audioMinutes,
        public int $estimatedPages,
        public int $themes,
        public int $chapters,
        public int $sensitiveUndecided,
    ) {}

    /**
     * Le volume : mots **ou** minutes.
     *
     * Un « ou » et non un « et », et c'est le point de R-6 : quelqu'un qui
     * parle beaucoup et dont la transcription est courte a autant de matière
     * que l'inverse.
     */
    public function meetsVolume(): bool
    {
        return $this->words >= self::threshold('min_words')
            || $this->audioMinutes >= (float) self::threshold('min_audio_minutes');
    }

    public function meetsPages(): bool
    {
        return $this->estimatedPages >= self::threshold('min_pages');
    }

    public function meetsThemes(): bool
    {
        return $this->themes >= self::threshold('min_themes');
    }

    /**
     * Aucun sujet sensible laissé sans décision.
     *
     * Imprimer un récit de santé ou de conviction sans que la personne ait dit
     * qui peut le lire serait exactement la faute que tout ce produit cherche
     * à éviter. Un `decide_later` ne compte pas : c'est le contraire d'une
     * décision.
     */
    public function meetsSensitiveReviewed(): bool
    {
        return $this->sensitiveUndecided === 0;
    }

    public function isReady(): bool
    {
        return $this->meetsVolume()
            && $this->meetsPages()
            && $this->meetsThemes()
            && $this->meetsSensitiveReviewed();
    }

    /**
     * Ce qui manque, en clés stables.
     *
     * Les libellés vivent dans `lang/fr` ; la liste sert la jauge et les
     * messages.
     *
     * @return list<string>
     */
    public function missing(): array
    {
        return array_values(array_filter([
            $this->meetsVolume() ? null : 'volume',
            $this->meetsPages() ? null : 'pages',
            $this->meetsThemes() ? null : 'themes',
            $this->meetsSensitiveReviewed() ? null : 'sensitive',
        ]));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'words' => $this->words,
            'audioMinutes' => round($this->audioMinutes, 1),
            'estimatedPages' => $this->estimatedPages,
            'themes' => $this->themes,
            'chapters' => $this->chapters,
            'sensitiveUndecided' => $this->sensitiveUndecided,
            'ready' => $this->isReady(),
            'missing' => $this->missing(),
            'thresholds' => [
                'words' => self::threshold('min_words'),
                'audioMinutes' => self::threshold('min_audio_minutes'),
                'pages' => self::threshold('min_pages'),
                'themes' => self::threshold('min_themes'),
            ],
        ];
    }

    private static function threshold(string $key): int
    {
        return (int) config("product.book_ready.{$key}");
    }
}
