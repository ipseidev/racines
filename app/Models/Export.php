<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ExportKind;
use App\Enums\ExportScope;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Un export : ce que la famille emporte.
 *
 * @property string $id
 * @property string $project_id
 * @property ExportKind $kind
 * @property string $status
 * @property ExportScope $scope
 * @property string|null $object_path
 * @property int|null $bytes
 * @property array<string, mixed>|null $manifest
 * @property CarbonImmutable|null $built_at
 * @property CarbonImmutable|null $expires_at
 * @property CarbonImmutable|null $downloaded_at
 * @property int $download_count
 * @property string|null $failure_reason
 * @property-read Project $project
 */
final class Export extends Model
{
    use HasUuids;

    /** Sept jours : assez pour partir en vacances, trop peu pour oublier. */
    public const DAYS = 7;

    protected $fillable = ['kind', 'scope', 'status'];

    protected $attributes = ['status' => 'queued'];

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return MorphTo<Model, $this> */
    public function requestedBy(): MorphTo
    {
        return $this->morphTo('requested_by');
    }

    public function isReady(): bool
    {
        return $this->status === 'ready'
            && $this->object_path !== null
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'kind' => ExportKind::class,
            'scope' => ExportScope::class,
            'manifest' => 'array',
            'bytes' => 'integer',
            'download_count' => 'integer',
            'built_at' => 'immutable_datetime',
            'expires_at' => 'immutable_datetime',
            'downloaded_at' => 'immutable_datetime',
        ];
    }
}
