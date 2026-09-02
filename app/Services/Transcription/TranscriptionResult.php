<?php

declare(strict_types=1);

namespace App\Services\Transcription;

/**
 * Ce qu'un fournisseur rend : le texte, et les mots horodatés.
 *
 * Les mots gardent la graphie **du fournisseur**, même quand le lexique
 * corrige le texte : ils servent à suivre l'audio, et décaler leur contenu
 * décalerait le suivi.
 */
final readonly class TranscriptionResult
{
    /**
     * @param  list<array{word: string, start: float, end: float, confidence: float|null}>  $words
     * @param  array<string, mixed>  $providerMetadata
     */
    public function __construct(
        public string $text,
        public array $words = [],
        public string $language = 'fr',
        public array $providerMetadata = [],
    ) {}

    public function isEmpty(): bool
    {
        return trim($this->text) === '';
    }

    /**
     * Durée déduite des mots, quand le fournisseur ne la donne pas.
     */
    public function spokenSeconds(): ?float
    {
        if ($this->words === []) {
            return null;
        }

        return $this->words[count($this->words) - 1]['end'];
    }
}
