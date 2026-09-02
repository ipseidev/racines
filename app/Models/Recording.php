<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\StoresDatesWithOffset;
use App\Enums\RecordingSource;
use App\Enums\UploadStatus;
use Carbon\CarbonImmutable;
use Database\Factories\RecordingFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un enregistrement audio. L'original est sacré : jamais remplacé.
 *
 * @property string $id
 * @property string $story_id
 * @property RecordingSource $source
 * @property string $original_disk
 * @property string|null $original_path
 * @property string|null $original_mime
 * @property int|null $original_bytes
 * @property string|null $duration_seconds
 * @property string|null $derived_mp3_path
 * @property string|null $replica_path
 * @property CarbonImmutable|null $replicated_at
 * @property string|null $upload_id
 * @property UploadStatus $upload_status
 * @property CarbonImmutable|null $confirmed_at
 * @property string|null $checksum_sha256
 * @property bool $is_current
 * @property array<int, array<string, mixed>>|null $segments
 * @property array<string, mixed>|null $device_info
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Story $story
 */
final class Recording extends Model
{
    /** @use HasFactory<RecordingFactory> */
    use HasFactory, HasUuids, StoresDatesWithOffset;

    /** @var array<string, mixed> */
    protected $attributes = [
        'source' => RecordingSource::Browser->value,
        'original_disk' => 'r2',
        'upload_status' => UploadStatus::Initiated->value,
        'is_current' => true,
    ];

    /** @var list<string> */
    protected $fillable = ['source', 'original_mime', 'segments', 'device_info'];

    /** @return BelongsTo<Story, $this> */
    public function story(): BelongsTo
    {
        return $this->belongsTo(Story::class);
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
     * Vrai seulement si le stockage a confirmé détenir l'objet.
     */
    public function isConfirmed(): bool
    {
        return $this->confirmed_at !== null;
    }

    public function segmentCount(): int
    {
        return count($this->segments ?? []);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'source' => RecordingSource::class,
            'upload_status' => UploadStatus::class,
            'original_bytes' => 'integer',
            'is_current' => 'boolean',
            'segments' => 'array',
            'device_info' => 'array',
            'replicated_at' => 'immutable_datetime',
            'confirmed_at' => 'immutable_datetime',
        ];
    }
}
