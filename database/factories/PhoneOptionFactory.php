<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PhoneOptionEntry;
use App\Enums\PhoneOptionStatus;
use App\Models\PhoneOption;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PhoneOption>
 */
final class PhoneOptionFactory extends Factory
{
    /** @var class-string<PhoneOption> */
    protected $model = PhoneOption::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'entry' => PhoneOptionEntry::Checkout,
            'status' => PhoneOptionStatus::Requested,
        ];
    }

    public function rescue(): static
    {
        return $this->state(fn (): array => ['entry' => PhoneOptionEntry::Rescue]);
    }

    public function active(): static
    {
        return $this->state(fn (): array => ['status' => PhoneOptionStatus::Active]);
    }

    /**
     * Annulée : elle ne compte plus dans le plafond, le créneau est libéré.
     */
    public function cancelled(): static
    {
        return $this->state(fn (): array => ['status' => PhoneOptionStatus::Cancelled]);
    }
}
