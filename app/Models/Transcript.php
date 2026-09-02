<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\StoresDatesWithOffset;
use App\Enums\TranscriptKind;
use Carbon\CarbonImmutable;
use Database\Factories\TranscriptFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

/**
 * Un texte d'histoire : verbatim, rendu Fluide, ou correction.
 *
 * Le verbatim ne se supprime pas tant que l'histoire vit. La règle est en
 * base, et redoublée ici : mieux vaut une exception lisible dans le code
 * qu'une erreur Postgres remontée à un écran.
 *
 * @property string $id
 * @property string $story_id
 * @property string|null $recording_id
 * @property TranscriptKind $kind
 * @property string|null $source_transcript_id
 * @property int $version
 * @property string|null $provider
 * @property string|null $provider_job_id
 * @property string $language
 * @property string $text
 * @property array<int, array<string, mixed>>|null $words
 * @property array<string, mixed>|null $metadata
 * @property string|null $edited_by_type
 * @property string|null $edited_by_id
 * @property bool $is_current
 * @property CarbonImmutable|null $created_at
 * @property-read Story $story
 */
final class Transcript extends Model
{
    /** @use HasFactory<TranscriptFactory> */
    use HasFactory, HasUuids, StoresDatesWithOffset;

    public const UPDATED_AT = null;

    /** @var array<string, mixed> */
    protected $attributes = ['version' => 1, 'language' => 'fr', 'is_current' => true];

    /** @var list<string> */
    protected $fillable = [
        'kind', 'version', 'provider', 'provider_job_id', 'language',
        'text', 'words', 'metadata', 'is_current',
    ];

    /** @return BelongsTo<Story, $this> */
    public function story(): BelongsTo
    {
        return $this->belongsTo(Story::class);
    }

    /** @return BelongsTo<Recording, $this> */
    public function recording(): BelongsTo
    {
        return $this->belongsTo(Recording::class);
    }

    /** @return BelongsTo<self, $this> */
    public function source(): BelongsTo
    {
        return $this->belongsTo(self::class, 'source_transcript_id');
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeCurrent(Builder $query): Builder
    {
        return $query->where('is_current', true);
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeOfKind(Builder $query, TranscriptKind $kind): Builder
    {
        return $query->where('kind', $kind->value);
    }

    /**
     * Le texte que la famille lira : la correction si elle existe, sinon le
     * rendu Fluide, sinon le verbatim. Jamais rien d'autre.
     */
    public static function readableFor(Story $story): ?self
    {
        foreach ([TranscriptKind::Edited, TranscriptKind::Fluide, TranscriptKind::Verbatim] as $kind) {
            $transcript = $story->transcripts()->current()->ofKind($kind)->first();

            if ($transcript instanceof self) {
                return $transcript;
            }
        }

        return null;
    }

    protected static function booted(): void
    {
        self::deleting(function (self $transcript): bool {
            if ($transcript->kind !== TranscriptKind::Verbatim) {
                return true;
            }

            if ($transcript->story->state->getValue() === 'deleted') {
                return true;
            }

            throw new RuntimeException(
                "Le verbatim de l'histoire [{$transcript->story_id}] ne se supprime pas tant qu'elle vit."
            );
        });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'kind' => TranscriptKind::class,
            'version' => 'integer',
            'words' => 'array',
            'metadata' => 'array',
            'is_current' => 'boolean',
        ];
    }
}
