<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lead>
 */
final class LeadFactory extends Factory
{
    /** @var class-string<Lead> */
    protected $model = Lead::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $email = fake()->unique()->safeEmail();

        return [
            'email' => $email,
            'discount_code' => Lead::generateCode(),
            'discount_percent' => 10,
            'source' => Lead::SOURCE_LANDING,
            'code_expires_at' => now()->addDays(Lead::CODE_LIFETIME_DAYS),
        ];
    }

    /**
     * L'empreinte suit l'adresse finale, y compris celle passée à `create()` :
     * une empreinte calculée sur l'adresse tirée au sort ne retrouverait rien.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (Lead $lead): void {
            $lead->email_hash = Lead::hashEmail($lead->email);
        });
    }

    public function used(): static
    {
        return $this->state(fn (): array => ['code_used_at' => now()->subDay()]);
    }

    public function expired(): static
    {
        return $this->state(fn (): array => ['code_expires_at' => now()->subDay()]);
    }

    public function wantsNews(): static
    {
        return $this->state(fn (): array => [
            'news_opted_in_at' => now(),
            'consent_text_version' => '1.0',
        ]);
    }
}
