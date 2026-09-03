<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CheckoutDraft;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CheckoutDraft>
 */
final class CheckoutDraftFactory extends Factory
{
    /** @var class-string<CheckoutDraft> */
    protected $model = CheckoutDraft::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'step' => 1,
            'payload' => ['for' => 'relative'],
            'expires_at' => now()->addDays(CheckoutDraft::LIFETIME_DAYS),
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (): array => ['expires_at' => now()->subDay()]);
    }
}
