<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Channel;
use App\Enums\OtpPurpose;
use App\Models\Narrator;
use App\Models\OtpChallenge;
use App\Services\Tokens\OtpService;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<OtpChallenge>
 *
 * L'empreinte du code est salée par l'identifiant du défi : la fabrique tire
 * donc l'identifiant elle-même pour pouvoir calculer l'empreinte du code
 * connu des tests.
 */
final class OtpChallengeFactory extends Factory
{
    /** @var class-string<OtpChallenge> */
    protected $model = OtpChallenge::class;

    public const CODE = '123456';

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $id = (string) Str::uuid7();

        return [
            'id' => $id,
            'narrator_id' => Narrator::factory()->primary(),
            'family_member_id' => null,
            'purpose' => OtpPurpose::SensitiveAct,
            'code_hash' => OtpService::hashCode(self::CODE, $id),
            'channel' => Channel::Sms,
            'sent_to_masked' => '+336•• •• •• 00',
            'expires_at' => now()->addMinutes(10),
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (): array => ['expires_at' => now()->subMinute()]);
    }

    public function locked(): static
    {
        return $this->state(fn (): array => [
            'attempts' => 5,
            'locked_until' => now()->addMinutes(15),
        ]);
    }

    public function forNarratorSpace(): static
    {
        return $this->state(fn (): array => ['purpose' => OtpPurpose::NarratorSpace]);
    }
}
