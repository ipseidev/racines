<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AnswerType;
use App\Enums\DeletionRequestedBy;
use App\Enums\ShareDecision;
use App\Enums\StoryVisibility;
use App\Enums\ValidatedVia;
use App\Models\Narrator;
use App\Models\Project;
use App\Models\Question;
use App\Models\Story;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Story>
 *
 * Seul endroit du code, avec les seeders, où `state` est écrit sans passer par
 * une transition : une fabrique construit un décor, elle ne joue pas une
 * histoire. Le test `NoDirectStateWriteTest` interdit cette écriture partout
 * ailleurs.
 */
final class StoryFactory extends Factory
{
    /** @var class-string<Story> */
    protected $model = Story::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'narrator_id' => Narrator::factory()->primary(),
            'project_id' => function (array $attributes): string {
                return (string) Narrator::query()->whereKey($attributes['narrator_id'])->firstOrFail()->project_id;
            },
            'question_id' => Question::factory(),
            'custom_question_text' => null,
            'sequence' => function (array $attributes): int {
                return 1 + (int) Story::query()->where('project_id', $attributes['project_id'])->max('sequence');
            },
            'state' => 'proposed',
            'proposed_at' => now(),
            'visibility' => StoryVisibility::AllFamily,
        ];
    }

    /**
     * Rattache l'histoire à un projet existant et à son narrateur principal.
     */
    public function forProject(Project $project): static
    {
        return $this->state(function () use ($project): array {
            $narrator = $project->primaryNarrator()->first()
                ?? Narrator::factory()->primary()->create(['project_id' => $project->id]);

            return [
                'project_id' => $project->id,
                'narrator_id' => $narrator->id,
            ];
        });
    }

    public function proposed(): static
    {
        return $this->state(fn (): array => $this->timeline('proposed'));
    }

    public function recorded(): static
    {
        return $this->state(fn (): array => $this->timeline('recorded'));
    }

    public function transcribed(): static
    {
        return $this->state(fn (): array => $this->timeline('transcribed'));
    }

    public function toReview(): static
    {
        return $this->state(fn (): array => $this->timeline('to_review'));
    }

    public function validated(): static
    {
        return $this->state(fn (): array => $this->timeline('validated'));
    }

    public function shared(): static
    {
        return $this->state(fn (): array => $this->timeline('shared'));
    }

    public function inBook(): static
    {
        return $this->state(fn (): array => $this->timeline('in_book'));
    }

    public function bookOnly(): static
    {
        return $this->state(fn (): array => ['visibility' => StoryVisibility::BookOnly]);
    }

    /**
     * Masquée depuis l'état transcrite, donc restaurable vers celui-là.
     */
    public function hidden(string $from = 'transcribed'): static
    {
        return $this->state(fn (): array => [
            ...$this->timeline($from),
            'state' => 'hidden',
            'previous_state' => $from,
            'hidden_at' => now(),
        ]);
    }

    public function archived(string $from = 'transcribed'): static
    {
        return $this->state(fn (): array => [
            ...$this->timeline($from),
            'state' => 'archived',
            'previous_state' => $from,
            'archived_at' => now(),
        ]);
    }

    public function trashed(string $from = 'transcribed'): static
    {
        return $this->state(fn (): array => [
            ...$this->timeline($from),
            'state' => 'trashed',
            'previous_state' => $from,
            'trashed_at' => now(),
        ]);
    }

    public function deleted(): static
    {
        return $this->state(fn (): array => [
            ...$this->timeline('transcribed'),
            'state' => 'deleted',
            'previous_state' => 'transcribed',
            'trashed_at' => now()->subDay(),
            'deleted_at' => now(),
            'deletion_requested_by' => DeletionRequestedBy::Narrator,
        ]);
    }

    /**
     * Horodatages cohérents avec l'état visé : une histoire partagée a bien
     * été enregistrée, transcrite puis validée avant.
     *
     * @return array<string, mixed>
     */
    private function timeline(string $state): array
    {
        $attributes = ['state' => $state, 'proposed_at' => now()->subDays(7)];

        $reached = static fn (string $step): bool => in_array($step, match ($state) {
            'proposed' => [],
            'recorded' => ['recorded'],
            'transcribed' => ['recorded', 'transcribed'],
            'to_review' => ['recorded', 'transcribed'],
            'validated' => ['recorded', 'transcribed', 'validated'],
            'shared' => ['recorded', 'transcribed', 'validated', 'shared'],
            'in_book' => ['recorded', 'transcribed', 'validated', 'shared'],
            default => [],
        }, true);

        if ($reached('recorded')) {
            $attributes['recorded_at'] = now()->subDays(6);
            $attributes['answer_type'] = AnswerType::Audio;
        }

        if ($reached('transcribed')) {
            $attributes['transcribed_at'] = now()->subDays(6);
        }

        if ($reached('validated')) {
            $attributes['share_decision'] = ShareDecision::Share;
            $attributes['share_decided_at'] = now()->subDays(6);
            $attributes['validated_at'] = now()->subDays(5);
            $attributes['validated_via'] = ValidatedVia::PostTranscription;
        }

        if ($reached('shared')) {
            $attributes['shared_at'] = now()->subDays(5);
        }

        return $attributes;
    }
}
