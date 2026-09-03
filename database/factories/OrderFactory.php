<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Order>
 */
final class OrderFactory extends Factory
{
    /** @var class-string<Order> */
    protected $model = Order::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'stripe_checkout_session_id' => 'cs_test_'.Str::random(24),
            'status' => OrderStatus::Pending,
            'currency' => 'eur',
            'subtotal_cents' => 4_900,
            'total_cents' => 4_900,
        ];
    }

    public function paid(): static
    {
        return $this->state(function (): array {
            $paidAt = now();

            return [
                'status' => OrderStatus::Paid,
                'stripe_payment_intent_id' => 'pi_test_'.Str::random(24),
                'paid_at' => $paidAt,
                'withdrawal_deadline_at' => $paidAt->addDays(14),
            ];
        });
    }

    /**
     * Une commande dont le délai de rétractation est passé.
     */
    public function withdrawalClosed(): static
    {
        return $this->paid()->state(fn (): array => [
            'paid_at' => now()->subDays(20),
            'withdrawal_deadline_at' => now()->subDays(6),
        ]);
    }

    public function refunded(): static
    {
        return $this->paid()->state(fn (array $attributes): array => [
            'status' => OrderStatus::Refunded,
            'refunded_cents' => $attributes['total_cents'] ?? 4_900,
        ]);
    }
}
