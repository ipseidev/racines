<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\StoresDatesWithOffset;
use App\Enums\OrderStatus;
use App\Enums\Sku;
use Carbon\CarbonImmutable;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Une commande payée, et ce qu'elle engage.
 *
 * @property string $id
 * @property int $user_id
 * @property string|null $project_id
 * @property string $stripe_checkout_session_id
 * @property string|null $stripe_payment_intent_id
 * @property string|null $stripe_invoice_url
 * @property OrderStatus $status
 * @property string $currency
 * @property int $subtotal_cents
 * @property int $total_cents
 * @property int $refunded_cents
 * @property int|null $price_variant Le prix de prévente vu par cet acheteur, en centimes.
 * @property CarbonImmutable|null $paid_at
 * @property CarbonImmutable|null $withdrawal_deadline_at
 * @property CarbonImmutable|null $service_started_at
 * @property-read User $user
 * @property-read Project|null $project
 * @property-read Collection<int, OrderItem> $items
 */
final class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory, HasUuids, StoresDatesWithOffset;

    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => OrderStatus::Pending->value,
        'currency' => 'eur',
        'refunded_cents' => 0,
    ];

    /** @var list<string> */
    protected $fillable = [
        'stripe_checkout_session_id', 'stripe_payment_intent_id', 'stripe_invoice_url',
        'status', 'currency', 'subtotal_cents', 'total_cents', 'refunded_cents',
        'price_variant', 'paid_at', 'withdrawal_deadline_at', 'service_started_at',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return HasMany<OrderItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * La rétractation est-elle encore ouverte ?
     *
     * Quatorze jours à compter du paiement, et le délai est **stocké** : une
     * règle qui change ne doit pas rétroagir sur une commande passée.
     */
    public function canBeWithdrawn(): bool
    {
        if (! $this->status->isPaid()) {
            return false;
        }

        return $this->withdrawal_deadline_at !== null
            && $this->withdrawal_deadline_at->isFuture();
    }

    /**
     * Le délai légal, à partir du paiement.
     */
    public static function withdrawalDeadlineFrom(CarbonImmutable $paidAt): CarbonImmutable
    {
        return $paidAt->addDays(14);
    }

    public function quantityOf(Sku $sku): int
    {
        return (int) $this->items->where('sku', $sku)->sum('quantity');
    }

    public function includes(Sku $sku): bool
    {
        return $this->quantityOf($sku) > 0;
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'subtotal_cents' => 'integer',
            'total_cents' => 'integer',
            'refunded_cents' => 'integer',
            'price_variant' => 'integer',
            'paid_at' => 'immutable_datetime',
            'withdrawal_deadline_at' => 'immutable_datetime',
            'service_started_at' => 'immutable_datetime',
        ];
    }
}
