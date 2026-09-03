<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\StoresDatesWithOffset;
use App\Enums\PhoneOptionEntry;
use App\Enums\PhoneOptionStatus;
use App\Enums\PromptSlot;
use Database\Factories\PhoneOptionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Une option « enregistrement par téléphone ».
 *
 * @property string $id
 * @property string $project_id
 * @property int|null $order_item_id
 * @property PhoneOptionEntry $entry
 * @property PhoneOptionStatus $status
 * @property int|null $operator_user_id
 * @property int|null $call_day
 * @property PromptSlot|null $call_slot
 * @property string|null $notes
 * @property-read Project $project
 */
final class PhoneOption extends Model
{
    /** @use HasFactory<PhoneOptionFactory> */
    use HasFactory, HasUuids, StoresDatesWithOffset;

    /** @var array<string, mixed> */
    protected $attributes = ['status' => PhoneOptionStatus::Requested->value];

    /** @var list<string> */
    protected $fillable = ['entry', 'status', 'call_day', 'call_slot', 'notes'];

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return BelongsTo<OrderItem, $this> */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    /**
     * Celles qui occupent un créneau humain.
     *
     * Une demande en attente compte autant qu'une option active : le créneau
     * est réservé dès qu'on l'a promis, et le compter seulement une fois
     * active ferait accepter onze familles pour dix appels possibles.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeCountingTowardsCap(Builder $query): Builder
    {
        return $query->whereIn('status', array_values(array_map(
            fn (PhoneOptionStatus $status): string => $status->value,
            array_filter(
                PhoneOptionStatus::cases(),
                fn (PhoneOptionStatus $status): bool => $status->countsTowardsCap(),
            ),
        )));
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'entry' => PhoneOptionEntry::class,
            'status' => PhoneOptionStatus::class,
            'call_slot' => PromptSlot::class,
            'call_day' => 'integer',
        ];
    }
}
