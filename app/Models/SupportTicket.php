<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\StoresDatesWithOffset;
use App\Enums\SupportTicketKind;
use App\Enums\SupportTicketStatus;
use Carbon\CarbonImmutable;
use Database\Factories\SupportTicketFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un ticket ouvert par le produit lui-même.
 *
 * @property int $id
 * @property string $project_id
 * @property string|null $story_id
 * @property SupportTicketKind $kind
 * @property SupportTicketStatus $status
 * @property array<string, mixed>|null $payload
 * @property CarbonImmutable $opened_at
 * @property CarbonImmutable|null $closed_at
 * @property int|null $closed_by_user_id
 */
final class SupportTicket extends Model
{
    /** @use HasFactory<SupportTicketFactory> */
    use HasFactory, StoresDatesWithOffset;

    /** @var array<string, mixed> */
    protected $attributes = ['status' => SupportTicketStatus::Open->value];

    /** @var list<string> */
    protected $fillable = ['kind', 'status', 'payload', 'opened_at'];

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return BelongsTo<Story, $this> */
    public function story(): BelongsTo
    {
        return $this->belongsTo(Story::class);
    }

    public function isOpen(): bool
    {
        return $this->status === SupportTicketStatus::Open;
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'kind' => SupportTicketKind::class,
            'status' => SupportTicketStatus::class,
            'payload' => 'array',
            'opened_at' => 'immutable_datetime',
            'closed_at' => 'immutable_datetime',
        ];
    }
}
