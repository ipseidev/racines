<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\StoresDatesWithOffset;
use App\Enums\Channel;
use Carbon\CarbonImmutable;
use Database\Factories\InvitationFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Une invitation envoyée, et ce que le narrateur en a fait.
 *
 * @property string $id
 * @property string $project_id
 * @property string $narrator_id
 * @property Channel $channel
 * @property int $attempt
 * @property string|null $token_id
 * @property CarbonImmutable $sent_at
 * @property CarbonImmutable|null $opened_at
 * @property CarbonImmutable|null $accepted_at
 * @property CarbonImmutable|null $refused_at
 * @property-read Narrator $narrator
 * @property-read Project $project
 */
final class Invitation extends Model
{
    /** @use HasFactory<InvitationFactory> */
    use HasFactory, HasUuids, StoresDatesWithOffset;

    /**
     * L'invitation, puis deux relances. Au-delà, ce n'est plus une
     * invitation, c'est une insistance (doc 04 §2).
     */
    public const MAX_ATTEMPTS = 3;

    /** @var list<string> */
    protected $fillable = ['channel', 'attempt', 'sent_at', 'opened_at', 'accepted_at', 'refused_at'];

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return BelongsTo<Narrator, $this> */
    public function narrator(): BelongsTo
    {
        return $this->belongsTo(Narrator::class);
    }

    /** @return BelongsTo<AccessToken, $this> */
    public function token(): BelongsTo
    {
        return $this->belongsTo(AccessToken::class, 'token_id');
    }

    /**
     * Combien d'envois ce narrateur a déjà reçus.
     */
    public static function attemptsFor(Narrator $narrator): int
    {
        return self::query()->where('narrator_id', $narrator->id)->count();
    }

    public static function canSendTo(Narrator $narrator): bool
    {
        return self::attemptsFor($narrator) < self::MAX_ATTEMPTS;
    }

    public function isAnswered(): bool
    {
        return $this->accepted_at !== null || $this->refused_at !== null;
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'channel' => Channel::class,
            'attempt' => 'integer',
            'sent_at' => 'immutable_datetime',
            'opened_at' => 'immutable_datetime',
            'accepted_at' => 'immutable_datetime',
            'refused_at' => 'immutable_datetime',
        ];
    }
}
