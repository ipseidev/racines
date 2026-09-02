<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\StoresDatesWithOffset;
use App\Enums\TokenType;
use Carbon\CarbonImmutable;
use Database\Factories\ListenEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * L'écoute d'une histoire par un proche : une ligne, cumulée.
 *
 * @property int $id
 * @property string $story_id
 * @property string|null $family_member_id
 * @property TokenType $token_type
 * @property int $seconds_listened
 * @property bool $reached_30s
 * @property CarbonImmutable|null $started_at
 * @property-read Story $story
 */
final class ListenEvent extends Model
{
    /** @use HasFactory<ListenEventFactory> */
    use HasFactory, StoresDatesWithOffset;

    /** @var list<string> */
    protected $fillable = ['token_type', 'seconds_listened', 'started_at'];

    /** @return BelongsTo<Story, $this> */
    public function story(): BelongsTo
    {
        return $this->belongsTo(Story::class);
    }

    /** @return BelongsTo<FamilyMember, $this> */
    public function familyMember(): BelongsTo
    {
        return $this->belongsTo(FamilyMember::class);
    }

    /**
     * Le seuil du dossier : trente secondes d'écoute, pas une page ouverte.
     */
    public const THRESHOLD_SECONDS = 30;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'token_type' => TokenType::class,
            'seconds_listened' => 'integer',
            'reached_30s' => 'boolean',
            'started_at' => 'immutable_datetime',
        ];
    }
}
