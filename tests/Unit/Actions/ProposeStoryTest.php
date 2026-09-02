<?php

declare(strict_types=1);

use App\Actions\ProposeStory;
use App\Enums\ProjectStatus;
use App\Exceptions\Domain\MissingPrimaryNarrator;
use App\Exceptions\Domain\ProjectNotCollecting;
use App\Models\Narrator;
use App\Models\Project;
use App\Models\Question;
use App\States\Story\Proposed;
use InvalidArgumentException;

function projectWithNarrator(array $attributes = []): Project
{
    $project = Project::factory()->create($attributes);
    Narrator::factory()->primary()->create(['project_id' => $project->id]);

    return $project->refresh();
}

it('propose une histoire à l’état proposée, horodatée et numérotée', function (): void {
    $this->freezeTime();

    $project = projectWithNarrator();
    $question = Question::factory()->create();

    $first = app(ProposeStory::class)->handle($project, $question);
    $second = app(ProposeStory::class)->handle($project, Question::factory()->create());

    expect($first->state)->toBeInstanceOf(Proposed::class)
        ->and($first->proposed_at?->getTimestamp())->toBe(now()->getTimestamp())
        ->and($first->sequence)->toBe(1)
        ->and($first->question_id)->toBe($question->id)
        ->and($first->narrator_id)->toBe($project->primaryNarrator()->value('id'))
        ->and($second->sequence)->toBe(2);
});

it('accepte une question personnalisée sans question du corpus', function (): void {
    $project = projectWithNarrator();

    $story = app(ProposeStory::class)->handle($project, null, 'Raconte-nous ton premier vélo.');

    expect($story->question_id)->toBeNull()
        ->and($story->questionText())->toBe('Raconte-nous ton premier vélo.');
});

it('refuse une histoire sans question ni texte personnalisé', function (): void {
    $project = projectWithNarrator();

    expect(fn () => app(ProposeStory::class)->handle($project))->toThrow(InvalidArgumentException::class);
});

it('refuse de proposer une histoire à un projet en pause', function (): void {
    $project = projectWithNarrator(['status' => ProjectStatus::Paused]);

    expect(fn () => app(ProposeStory::class)->handle($project, Question::factory()->create()))
        ->toThrow(ProjectNotCollecting::class);

    expect($project->stories()->count())->toBe(0);
});

it('refuse de proposer une histoire à un projet gelé par un deuil', function (): void {
    $project = projectWithNarrator(['status' => ProjectStatus::FrozenBereavement]);

    expect(fn () => app(ProposeStory::class)->handle($project, Question::factory()->create()))
        ->toThrow(ProjectNotCollecting::class);
});

it('refuse de proposer une histoire à un projet sans narrateur principal', function (): void {
    $project = Project::factory()->create();

    expect(fn () => app(ProposeStory::class)->handle($project, Question::factory()->create()))
        ->toThrow(MissingPrimaryNarrator::class);
});
