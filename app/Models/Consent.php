<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\StoresDatesWithOffset;
use App\Enums\ConsentChannel;
use App\Enums\ConsentKind;
use App\Enums\ConsentStatus;
use Carbon\CarbonImmutable;
use Database\Factories\ConsentFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Une ligne par acte de consentement, jamais modifiée (doc 04 §2).
 *
 * @property string $id
 * @property string $project_id
 * @property string $subject_type
 * @property string $subject_id
 * @property ConsentKind $kind
 * @property ConsentStatus $status
 * @property ConsentChannel $channel
 * @property string $text_version
 * @property string|null $ip_hash
 * @property string|null $user_agent
 * @property CarbonImmutable $granted_at
 * @property CarbonImmutable|null $revoked_at
 * @property int|null $recorded_by_user_id
 * @property CarbonImmutable|null $created_at
 */
final class Consent extends Model
{
    /** @use HasFactory<ConsentFactory> */
    use HasFactory, HasUuids, StoresDatesWithOffset;

    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $fillable = [
        'project_id', 'kind', 'status', 'channel', 'text_version',
        'ip_hash', 'user_agent', 'granted_at', 'revoked_at', 'recorded_by_user_id',
    ];

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return MorphTo<Model, $this> */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return BelongsTo<User, $this> */
    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }

    public function isGranted(): bool
    {
        return $this->status === ConsentStatus::Granted;
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'kind' => ConsentKind::class,
            'status' => ConsentStatus::class,
            'subject_id' => 'string',
            'channel' => ConsentChannel::class,
            'granted_at' => 'immutable_datetime',
            'revoked_at' => 'immutable_datetime',
        ];
    }
}
