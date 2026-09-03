<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Sku;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
final class OrderItemFactory extends Factory
{
    /** @var class-string<OrderItem> */
    protected $model = OrderItem::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'sku' => Sku::Pilot,
            'quantity' => 1,
            'unit_cents' => 4_900,
        ];
    }

    public function ofSku(Sku $sku, int $unitCents, int $quantity = 1): static
    {
        return $this->state(fn (): array => [
            'sku' => $sku,
            'unit_cents' => $unitCents,
            'quantity' => $quantity,
        ]);
    }
}
