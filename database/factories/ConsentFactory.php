<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ConsentChannel;
use App\Enums\ConsentKind;
use App\Enums\ConsentStatus;
use App\Models\Consent;
use App\Models\Narrator;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Consent>
 */
final class ConsentFactory extends Factory
{
    /** @var class-string<Consent> */
    protected $model = Consent::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'subject_type' => (new Narrator)->getMorphClass(),
            'subject_id' => Narrator::factory()->primary(),
            'project_id' => function (array $attributes): string {
                return (string) Narrator::query()->whereKey($attributes['subject_id'])->firstOrFail()->project_id;
            },
            'kind' => ConsentKind::VoiceRecording,
            'status' => ConsentStatus::Granted,
            'channel' => ConsentChannel::Web,
            'text_version' => '1.0',
            'granted_at' => now(),
        ];
    }

    public function revoked(): static
    {
        return $this->state(fn (): array => [
            'status' => ConsentStatus::Revoked,
            'revoked_at' => now(),
        ]);
    }

    /**
     * Consentement du narrateur au moment d'un appel téléphonique : la ligne
     * n'est valable que si un opérateur nommé l'a recueillie (D-9).
     */
    public function byPhoneOperator(int $operatorUserId): static
    {
        return $this->state(fn (): array => [
            'kind' => ConsentKind::PhoneCallRecording,
            'channel' => ConsentChannel::Phone,
            'recorded_by_user_id' => $operatorUserId,
        ]);
    }
}
