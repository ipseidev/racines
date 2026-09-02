<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CohortPhase;
use App\Models\Cohort;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cohort>
 */
final class CohortFactory extends Factory
{
    /** @var class-string<Cohort> */
    protected $model = Cohort::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'name' => 'Cohorte '.fake()->unique()->numberBetween(1, 999),
            'phase' => CohortPhase::Phase0A,
            'started_at' => now(),
            'notes' => null,
        ];
    }

    public function launch(): static
    {
        return $this->state(fn (): array => ['phase' => CohortPhase::Launch]);
    }
}
