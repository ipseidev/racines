<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Channel;
use App\Models\Invitation;
use App\Models\Narrator;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invitation>
 */
final class InvitationFactory extends Factory
{
    /** @var class-string<Invitation> */
    protected $model = Invitation::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'narrator_id' => Narrator::factory()->primary(),
            'project_id' => fn (array $attributes): string => (string) Narrator::query()
                ->whereKey($attributes['narrator_id'])->firstOrFail()->project_id,
            'channel' => Channel::Sms,
            'attempt' => 1,
            'sent_at' => now(),
        ];
    }

    public function accepted(): static
    {
        return $this->state(fn (): array => [
            'opened_at' => now()->subMinutes(5),
            'accepted_at' => now(),
        ]);
    }

    public function refused(): static
    {
        return $this->state(fn (): array => [
            'opened_at' => now()->subMinutes(5),
            'refused_at' => now(),
        ]);
    }
}
