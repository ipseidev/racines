<?php

declare(strict_types=1);

namespace App\States\Story\Transitions;

use App\Models\Story;
use App\States\Story\Archived;
use Spatie\ModelStates\Transition;

/**
 * → ARCHIVÉE. Sortie du fil courant, contenu conservé. Le retour depuis
 * l'archive s'ouvre au bloc 07 avec l'interface des retraits ; `previous_state`
 * est déjà mémorisé ici pour que ce retour soit possible sans migration.
 */
final class ArchiveStory extends Transition
{
    public function __construct(private readonly Story $story) {}

    public function handle(): Story
    {
        $this->story->previous_state = $this->story->state->getValue();
        $this->story->state = new Archived($this->story);
        $this->story->archived_at = now();
        $this->story->save();

        return $this->story;
    }
}
