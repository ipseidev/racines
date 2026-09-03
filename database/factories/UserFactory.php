<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
final class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'role' => UserRole::default(),
            'password' => self::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function admin(): static
    {
        return $this->state(fn (): array => ['role' => UserRole::Admin]);
    }

    public function support(): static
    {
        return $this->state(fn (): array => ['role' => UserRole::Support]);
    }

    public function supportReadonly(): static
    {
        return $this->state(fn (): array => ['role' => UserRole::SupportReadonly]);
    }

    /**
     * Un compte dont la double authentification est déjà configurée.
     *
     * Le secret est chiffré comme le fait Fortify, et les codes de
     * récupération sont posés : sans eux, `AppAuthentication::isEnabled()`
     * rend vrai mais l'écran de récupération casserait. Un décor doit
     * ressembler au produit, sinon il ne prouve rien.
     */
    public function withAppAuthentication(): static
    {
        return $this->state(fn (): array => [
            'two_factor_secret' => encrypt('JBSWY3DPEHPK3PXP'),
            'two_factor_recovery_codes' => encrypt((string) json_encode([
                'aaaa-bbbb', 'cccc-dddd', 'eeee-ffff',
            ])),
            'two_factor_confirmed_at' => now(),
        ]);
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the model has two-factor authentication configured.
     */
    public function withTwoFactor(): static
    {
        return $this->state(fn (array $attributes) => [
            'two_factor_secret' => encrypt('secret'),
            'two_factor_recovery_codes' => encrypt(json_encode(['recovery-code-1'])),
            'two_factor_confirmed_at' => now(),
        ]);
    }
}
