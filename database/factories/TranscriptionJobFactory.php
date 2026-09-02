<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TranscriptionStatus;
use App\Models\Recording;
use App\Models\TranscriptionJob;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TranscriptionJob>
 */
final class TranscriptionJobFactory extends Factory
{
    /** @var class-string<TranscriptionJob> */
    protected $model = TranscriptionJob::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'recording_id' => Recording::factory()->confirmed(),
            'provider' => 'fake',
            'status' => TranscriptionStatus::Queued,
            'attempts' => 0,
        ];
    }

    public function processing(?string $providerJobId = null): static
    {
        return $this->state(fn (): array => [
            'status' => TranscriptionStatus::Processing,
            'provider_job_id' => $providerJobId ?? 'job-'.fake()->unique()->numerify('######'),
            'submitted_at' => now(),
            'attempts' => 1,
        ]);
    }

    public function done(): static
    {
        return $this->state(fn (): array => [
            'status' => TranscriptionStatus::Done,
            'submitted_at' => now()->subMinute(),
            'completed_at' => now(),
            'attempts' => 1,
        ]);
    }

    public function failed(string $error = 'le fournisseur a rendu une erreur'): static
    {
        return $this->state(fn (): array => [
            'status' => TranscriptionStatus::Failed,
            'error' => $error,
            'attempts' => 3,
        ]);
    }
}
