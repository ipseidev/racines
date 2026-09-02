<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ReactionType;
use App\Models\FamilyMember;
use App\Models\Reaction;
use App\Models\Story;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reaction>
 */
final class ReactionFactory extends Factory
{
    /** @var class-string<Reaction> */
    protected $model = Reaction::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $story = Story::factory()->shared();

        return [
            'story_id' => $story,
            'family_member_id' => fn (array $attributes): string => (string) FamilyMember::factory()->create([
                'project_id' => Story::query()->whereKey($attributes['story_id'])->firstOrFail()->project_id,
            ])->id,
            'type' => ReactionType::Heart,
            'comment' => null,
        ];
    }

    public function thanks(?string $comment = null): static
    {
        return $this->state(fn (): array => [
            'type' => ReactionType::Thanks,
            'comment' => $comment,
        ]);
    }
}
