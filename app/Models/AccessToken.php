<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TokenIssuedReason;
use App\Enums\TokenType;
use Carbon\CarbonImmutable;
use Database\Factories\AccessTokenFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Un lien du produit, stocké haché (doc 04 §12).
 *
 * Le jeton en clair n'existe qu'entre son émission et son envoi : il n'est
 * jamais relu depuis la base, seulement comparé par empreinte.
 *
 * @property string $id
 * @property TokenType $type
 * @property string $token_hash
 * @property string $subject_type
 * @property string $subject_id
 * @property list<string>|null $scope
 * @property CarbonImmutable|null $expires_at
 * @property bool $single_use
 * @property CarbonImmutable|null $used_at
 * @property CarbonImmutable|null $revoked_at
 * @property string|null $replaced_by_token_id
 * @property string|null $issued_by_type
 * @property string|null $issued_by_id
 * @property TokenIssuedReason $issued_reason
 * @property CarbonImmutable|null $last_used_at
 * @property int $use_count
 * @property CarbonImmutable|null $created_at
 */
final class AccessToken extends Model
{
    /** @use HasFactory<AccessTokenFactory> */
    use HasFactory, HasUuids;

    public const UPDATED_AT = null;

    /** @var array<string, mixed> */
    protected $attributes = [
        'single_use' => false,
        'use_count' => 0,
        'issued_reason' => TokenIssuedReason::Initial->value,
    ];

    /**
     * Le hash n'est pas assignable en masse : il ne se pose que dans
     * `TokenService::issue()`, à partir d'un jeton fraîchement tiré.
     *
     * @var list<string>
     */
    protected $fillable = ['type', 'scope', 'expires_at', 'single_use', 'issued_reason'];

    /** @return MorphTo<Model, $this> */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return MorphTo<Model, $this> */
    public function issuedBy(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return BelongsTo<self, $this> */
    public function replacedBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'replaced_by_token_id');
    }

    /**
     * Jetons encore utilisables : ni révoqués, ni expirés, ni consommés.
     *
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->whereNull('revoked_at')
            ->where(fn (Builder $q): Builder => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->where(fn (Builder $q): Builder => $q->where('single_use', false)->orWhereNull('used_at'));
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function isUsed(): bool
    {
        return $this->single_use && $this->used_at !== null;
    }

    public function isUsable(): bool
    {
        return ! $this->isRevoked() && ! $this->isExpired() && ! $this->isUsed();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => TokenType::class,
            'issued_reason' => TokenIssuedReason::class,
            'subject_id' => 'string',
            'issued_by_id' => 'string',
            'scope' => 'array',
            'single_use' => 'boolean',
            'use_count' => 'integer',
            'expires_at' => 'immutable_datetime',
            'used_at' => 'immutable_datetime',
            'revoked_at' => 'immutable_datetime',
            'last_used_at' => 'immutable_datetime',
        ];
    }
}
