<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TokenType;
use App\Models\FamilyMember;
use App\Models\ListenEvent;
use App\Models\Story;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ListenEvent>
 */
final class ListenEventFactory extends Factory
{
    /** @var class-string<ListenEvent> */
    protected $model = ListenEvent::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'story_id' => Story::factory()->shared(),
            'family_member_id' => fn (array $attributes): string => (string) FamilyMember::factory()->create([
                'project_id' => Story::query()->whereKey($attributes['story_id'])->firstOrFail()->project_id,
            ])->id,
            'token_type' => TokenType::ListenProject,
            'seconds_listened' => 12,
            'reached_30s' => false,
            'started_at' => now(),
        ];
    }

    public function listened(): static
    {
        return $this->state(fn (): array => [
            'seconds_listened' => 42,
            'reached_30s' => true,
        ]);
    }
}
