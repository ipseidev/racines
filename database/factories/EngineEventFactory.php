<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\EngineOutcome;
use App\Enums\EngineRuleId;
use App\Models\EngineEvent;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<EngineEvent>
 */
final class EngineEventFactory extends Factory
{
    /** @var class-string<EngineEvent> */
    protected $model = EngineEvent::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $key = (string) Str::uuid7().':-:1';

        return [
            'project_id' => Project::factory(),
            'rule_id' => EngineRuleId::LinkNotOpened,
            'occurrence_key' => $key,
            'dedupe_key' => EngineRuleId::LinkNotOpened->value.':'.$key,
            'fired_at' => now(),
            'action_taken' => ['told' => 'narrator'],
        ];
    }

    /**
     * Un déclenchement consigné mais non envoyé : une règle plus prioritaire
     * avait déjà parlé au narrateur ce jour-là.
     */
    public function suppressed(EngineRuleId $by = EngineRuleId::LinkNotOpened): static
    {
        return $this->state(fn (): array => [
            'action_taken' => ['suppressed_by' => $by->value, 'would_have_told' => 'narrator'],
        ]);
    }

    public function resumed(): static
    {
        return $this->state(fn (): array => [
            'outcome' => EngineOutcome::Resumed,
            'outcome_at' => now(),
        ]);
    }
}
