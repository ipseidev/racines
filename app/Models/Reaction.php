<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\StoresDatesWithOffset;
use App\Enums\ReactionType;
use Carbon\CarbonImmutable;
use Database\Factories\ReactionFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ce qu'un proche répond à une histoire.
 *
 * @property string $id
 * @property string $story_id
 * @property string $family_member_id
 * @property ReactionType $type
 * @property string|null $comment
 * @property CarbonImmutable|null $created_at
 * @property-read Story $story
 * @property-read FamilyMember $familyMember
 */
final class Reaction extends Model
{
    /** @use HasFactory<ReactionFactory> */
    use HasFactory, HasUuids, StoresDatesWithOffset;

    /** @var list<string> */
    protected $fillable = ['type', 'comment'];

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

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => ReactionType::class,
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ];
    }
}
