<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\StoresDatesWithOffset;
use App\Enums\Sku;
use Database\Factories\OrderItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Une ligne de commande.
 *
 * @property int $id
 * @property string $order_id
 * @property Sku $sku
 * @property int $quantity
 * @property int $unit_cents
 * @property string|null $stripe_price_id
 * @property array<string, mixed>|null $metadata
 * @property-read Order $order
 */
final class OrderItem extends Model
{
    /** @use HasFactory<OrderItemFactory> */
    use HasFactory, StoresDatesWithOffset;

    /** @var list<string> */
    protected $fillable = ['sku', 'quantity', 'unit_cents', 'stripe_price_id', 'metadata'];

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function totalCents(): int
    {
        return $this->unit_cents * $this->quantity;
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'sku' => Sku::class,
            'quantity' => 'integer',
            'unit_cents' => 'integer',
            'metadata' => 'array',
        ];
    }
}
