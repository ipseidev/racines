<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\FamilyMember;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FamilyMember>
 */
final class FamilyMemberFactory extends Factory
{
    /** @var class-string<FamilyMember> */
    protected $model = FamilyMember::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'invited_by_user_id' => User::factory(),
            'display_name' => fake()->firstName(),
            'relationship' => fake()->randomElement(['Fils', 'Fille', 'Petit-fils', 'Petite-fille', 'Nièce']),
            'email' => fake()->unique()->safeEmail(),
            'phone_e164' => null,
            'can_contribute' => false,
            'invited_at' => now(),
        ];
    }

    public function contributor(): static
    {
        return $this->state(fn (): array => ['can_contribute' => true]);
    }
}
