<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Actions\GrantMandate;
use App\Enums\ConsentKind;
use App\Models\Consent;
use App\Models\FamilyMember;
use App\Models\Mandate;
use App\Models\Narrator;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Mandate>
 */
final class MandateFactory extends Factory
{
    /** @var class-string<Mandate> */
    protected $model = Mandate::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $narrator = Narrator::factory()->primary();

        return [
            'narrator_id' => $narrator,
            'project_id' => fn (array $attributes): string => (string) Narrator::query()
                ->whereKey($attributes['narrator_id'])->firstOrFail()->project_id,
            'holder_type' => (new FamilyMember)->getMorphClass(),
            'holder_id' => fn (array $attributes): string => (string) FamilyMember::factory()->create([
                'project_id' => $attributes['project_id'],
            ])->id,
            'consent_id' => fn (array $attributes): string => (string) Consent::factory()->create([
                'subject_id' => $attributes['narrator_id'],
                'project_id' => $attributes['project_id'],
                'kind' => ConsentKind::MandateDelegation,
            ])->id,
            'scope' => GrantMandate::DEFAULT_SCOPE,
            'granted_at' => now(),
        ];
    }

    public function revoked(): static
    {
        return $this->state(fn (): array => ['revoked_at' => now()]);
    }
}
