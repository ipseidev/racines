<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\Domain\MissingPrimaryNarrator;
use App\Exceptions\Domain\ProjectNotCollecting;
use App\Models\Project;
use App\Models\Question;
use App\Models\Story;
use InvalidArgumentException;

/**
 * Propose une question au narrateur principal : c'est la naissance d'une
 * histoire, à l'état `proposed`.
 *
 * L'état n'est pas écrit ici : la machine d'états pose `Proposed` par défaut.
 * Un projet en pause ou gelé par un deuil ne reçoit rien — c'est le respect du
 * silence demandé, pas une limite technique.
 */
final class ProposeStory
{
    public function handle(Project $project, ?Question $question = null, ?string $customText = null): Story
    {
        if ($question === null && ($customText === null || trim($customText) === '')) {
            throw new InvalidArgumentException('A story needs either a corpus question or a custom question text.');
        }

        if (! $project->status->acceptsNewStories()) {
            throw ProjectNotCollecting::status($project->status->value);
        }

        $narrator = $project->primaryNarrator()->first();

        if ($narrator === null) {
            throw MissingPrimaryNarrator::forProject($project->id);
        }

        $story = new Story([
            'question_id' => $question?->id,
            'custom_question_text' => $question === null ? $customText : null,
            'sequence' => 1 + (int) $project->stories()->max('sequence'),
            'proposed_at' => now(),
        ]);

        $story->project()->associate($project);
        $story->narrator()->associate($narrator);
        $story->save();

        return $story;
    }
}
