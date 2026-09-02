<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TokenIssuedReason;
use App\Enums\TokenType;
use App\Models\AccessToken;
use App\Models\Story;
use App\Services\Tokens\TokenService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AccessToken>
 *
 * La fabrique ne conserve pas le jeton en clair : les tests qui ont besoin de
 * présenter un lien passent par `TokenService::issue()`, qui le rend une fois.
 */
final class AccessTokenFactory extends Factory
{
    /** @var class-string<AccessToken> */
    protected $model = AccessToken::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'type' => TokenType::Record,
            'token_hash' => TokenService::hash(TokenService::generate()),
            'subject_type' => (new Story)->getMorphClass(),
            'subject_id' => Story::factory()->recorded(),
            'single_use' => false,
            'issued_reason' => TokenIssuedReason::Initial,
            'expires_at' => now()->addDays(30),
        ];
    }

    public function revoked(): static
    {
        return $this->state(fn (): array => ['revoked_at' => now()->subHour()]);
    }

    public function expired(): static
    {
        return $this->state(fn (): array => ['expires_at' => now()->subDay()]);
    }

    public function ofType(TokenType $type): static
    {
        return $this->state(fn (): array => [
            'type' => $type,
            'single_use' => $type->isSingleUse(),
        ]);
    }
}
