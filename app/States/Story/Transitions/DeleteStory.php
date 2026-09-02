<?php

declare(strict_types=1);

namespace App\States\Story\Transitions;

use App\Enums\DeletionRequestedBy;
use App\Models\Story;
use App\States\Story\Deleted;
use Spatie\ModelStates\Transition;

/**
 * CORBEILLE → SUPPRIMÉE. État terminal : aucune transition n'en part.
 *
 * La transition consigne qui a demandé la suppression ; la purge des objets
 * stockés est le travail de `PurgeDeletedStory` (bloc 07), jamais celui d'une
 * transition.
 */
final class DeleteStory extends Transition
{
    public function __construct(
        private readonly Story $story,
        private readonly DeletionRequestedBy $requestedBy = DeletionRequestedBy::Narrator,
    ) {}

    public function handle(): Story
    {
        $this->story->state = new Deleted($this->story);
        $this->story->deleted_at = now();
        $this->story->deletion_requested_by = $this->requestedBy;
        $this->story->save();

        return $this->story;
    }
}
