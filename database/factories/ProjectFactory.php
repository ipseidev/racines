<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AddressForm;
use App\Enums\Cadence;
use App\Enums\Offer;
use App\Enums\ProjectStatus;
use App\Enums\PromptSlot;
use App\Enums\ValidationVariant;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
final class ProjectFactory extends Factory
{
    /** @var class-string<Project> */
    protected $model = Project::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'owner_user_id' => User::factory(),
            'cohort_id' => null,
            'status' => ProjectStatus::Active,
            'offer' => Offer::Pilot,
            'address_form' => AddressForm::Vous,
            'cadence' => Cadence::Weekly,
            'prompt_day' => 1,
            'prompt_slot' => PromptSlot::Morning,
            'timezone' => 'Europe/Paris',
            'validation_variant' => ValidationVariant::Immediate,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (): array => ['status' => ProjectStatus::Draft]);
    }

    public function paused(): static
    {
        return $this->state(fn (): array => [
            'status' => ProjectStatus::Paused,
            'paused_until' => now()->addWeeks(2),
        ]);
    }

    public function frozenByBereavement(): static
    {
        return $this->state(fn (): array => ['status' => ProjectStatus::FrozenBereavement]);
    }

    public function core(): static
    {
        return $this->state(fn (): array => ['offer' => Offer::Core]);
    }

    public function collecting(): static
    {
        return $this->afterCreating(fn (Project $project): Project => $project->startCollection());
    }
}
