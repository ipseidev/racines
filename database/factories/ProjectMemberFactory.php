<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ProjectMemberRole;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectMember>
 */
final class ProjectMemberFactory extends Factory
{
    /** @var class-string<ProjectMember> */
    protected $model = ProjectMember::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'user_id' => User::factory(),
            'role' => ProjectMemberRole::Initiator,
        ];
    }

    public function editor(): static
    {
        return $this->state(fn (): array => ['role' => ProjectMemberRole::Editor]);
    }
}
