<?php

declare(strict_types=1);

namespace App\Engine;

use App\Enums\EngineRuleId;
use App\Models\Narrator;
use App\Models\Project;
use App\Models\Story;

/**
 * Une occasion de parler, détectée par une règle.
 *
 * La clé d'idempotence est calculée **ici** et non dans chaque règle : la
 * formule est la même pour les onze (`projet:histoire|clé:tentative`), et
 * onze copies d'une même formule finissent par diverger — celle qui diverge
 * envoie deux fois le même message à la même personne (écart T-95).
 */
final readonly class Occurrence
{
    public function __construct(
        public Project $project,
        public ?Story $story = null,
        public ?Narrator $narrator = null,
        /** Discriminant quand l'occurrence ne porte pas sur une histoire. */
        public string $key = '',
        /** Numéro de relance : c'est lui qui distingue un rappel du suivant. */
        public int $attempt = 1,
    ) {}

    public function occurrenceKey(): string
    {
        $subject = $this->story === null
            ? ($this->key !== '' ? $this->key : '-')
            : $this->story->id;

        return implode(':', [$this->project->id, $subject, $this->attempt]);
    }

    public function dedupeKey(EngineRuleId $rule): string
    {
        return $rule->value.':'.$this->occurrenceKey();
    }
}
