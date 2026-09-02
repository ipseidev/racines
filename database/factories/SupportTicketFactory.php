<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\SupportTicketKind;
use App\Enums\SupportTicketStatus;
use App\Models\Project;
use App\Models\SupportTicket;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupportTicket>
 */
final class SupportTicketFactory extends Factory
{
    /** @var class-string<SupportTicket> */
    protected $model = SupportTicket::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'kind' => SupportTicketKind::MicDeniedTwice,
            'status' => SupportTicketStatus::Open,
            'payload' => [],
            'opened_at' => now(),
        ];
    }

    public function closed(): static
    {
        return $this->state(fn (): array => [
            'status' => SupportTicketStatus::Closed,
            'closed_at' => now(),
        ]);
    }
}
