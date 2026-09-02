<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Channel;
use App\Models\Narrator;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Narrator>
 */
final class NarratorFactory extends Factory
{
    /** @var class-string<Narrator> */
    protected $model = Narrator::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $firstName = fake()->firstName();

        return [
            'project_id' => Project::factory(),
            'first_name' => $firstName,
            'last_name' => fake()->lastName(),
            'display_name' => $firstName,
            'email' => null,
            'phone_e164' => '+336'.fake()->unique()->numerify('########'),
            'preferred_channel' => Channel::Sms,
            'is_primary' => false,
            'birth_year' => fake()->numberBetween(1930, 1960),
            'opted_in_at' => now(),
        ];
    }

    public function primary(): static
    {
        return $this->state(fn (): array => ['is_primary' => true]);
    }

    public function byEmail(): static
    {
        return $this->state(fn (): array => [
            'email' => fake()->unique()->safeEmail(),
            'phone_e164' => null,
            'preferred_channel' => Channel::Email,
        ]);
    }
}
