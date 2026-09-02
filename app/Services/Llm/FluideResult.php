<?php

declare(strict_types=1);

namespace App\Services\Llm;

/**
 * Ce que rend une mise au propre.
 *
 * `refused` n'est pas un échec technique : c'est le modèle qui décline. On le
 * traite comme une information — le verbatim reste, la famille garde le récit,
 * et le support regarde. Un livre de souvenirs n'a pas à être « sauvé » en
 * silence par un autre modèle.
 */
final readonly class FluideResult
{
    /**
     * @param  list<string>  $themes
     * @param  list<string>  $properNouns
     * @param  list<string>  $sensitiveFlags
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public ?string $title,
        public string $text,
        public array $themes = [],
        public array $properNouns = [],
        public array $sensitiveFlags = [],
        public bool $refused = false,
        public array $metadata = [],
    ) {}

    /**
     * @param  array<string, mixed>  $metadata
     */
    public static function refused(array $metadata = []): self
    {
        return new self(
            title: null,
            text: '',
            refused: true,
            metadata: $metadata,
        );
    }
}
