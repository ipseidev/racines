<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Story;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Le texte d'une histoire est prêt à être relu.
 *
 * Émis **même quand la mise au propre n'a pas eu lieu** — consentement absent,
 * ou refus du modèle. Le narrateur a droit à sa relecture dans tous les cas :
 * c'est son récit, pas celui de l'outil qui l'a mis en forme. Le bloc 07
 * écoute cet événement pour lancer la validation.
 */
final class TranscriptionReady
{
    use Dispatchable;

    public function __construct(
        public readonly Story $story,
        public readonly bool $rendered,
    ) {}
}
