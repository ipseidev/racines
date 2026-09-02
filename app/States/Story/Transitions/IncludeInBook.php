<?php

declare(strict_types=1);

namespace App\States\Story\Transitions;

use App\Enums\StoryVisibility;
use App\Exceptions\Domain\ForbiddenTransition;
use App\Models\Story;
use App\States\Story\InBook;
use App\States\Story\Validated;
use Spatie\ModelStates\Transition;

/**
 * PARTAGÉE → INCLUSE AU LIVRE, ou VALIDÉE → INCLUSE AU LIVRE quand le
 * narrateur a choisi « livre uniquement » (glossaire §3).
 */
final class IncludeInBook extends Transition
{
    public function __construct(private readonly Story $story) {}

    public function handle(): Story
    {
        if ($this->story->state instanceof Validated && $this->story->visibility !== StoryVisibility::BookOnly) {
            throw ForbiddenTransition::guardFailed(
                'validated',
                'in_book',
                'a validated story joins the book once shared, unless its visibility is book_only',
            );
        }

        $this->story->state = new InBook($this->story);
        $this->story->save();

        return $this->story;
    }
}
