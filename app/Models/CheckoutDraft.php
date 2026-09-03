<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\StoresDatesWithOffset;
use Carbon\CarbonImmutable;
use Database\Factories\CheckoutDraftFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un tunnel d'achat commencé et pas fini.
 *
 * @property string $id
 * @property int|null $user_id
 * @property int $step
 * @property array<string, mixed> $payload
 * @property int|null $price_variant Le prix de prévente vu par cet acheteur, en centimes.
 * @property CarbonImmutable $expires_at
 */
final class CheckoutDraft extends Model
{
    /** @use HasFactory<CheckoutDraftFactory> */
    use HasFactory, HasUuids, StoresDatesWithOffset;

    /**
     * Sept jours. Le brouillon contient le prénom d'un parent et un numéro de
     * téléphone : ce n'est pas une donnée qu'on garde « au cas où ».
     */
    public const LIFETIME_DAYS = 7;

    /** @var list<string> */
    protected $fillable = ['step', 'payload', 'price_variant', 'expires_at'];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Une valeur du brouillon, en notation pointée.
     */
    public function value(string $key, mixed $default = null): mixed
    {
        return data_get($this->payload, $key, $default);
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function merge(array $values, ?int $step = null): self
    {
        $this->payload = [...$this->payload, ...$values];

        if ($step !== null) {
            // On ne recule jamais l'étape atteinte : quelqu'un qui revient en
            // arrière pour corriger un champ ne doit pas perdre la suite.
            $this->step = max($this->step, $step);
        }

        $this->expires_at = now()->addDays(self::LIFETIME_DAYS);
        $this->save();

        return $this;
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'step' => 'integer',
            'price_variant' => 'integer',
            'expires_at' => 'immutable_datetime',
        ];
    }
}
