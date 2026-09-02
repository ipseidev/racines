<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un événement rapporté par le navigateur du narrateur. Table interne.
 *
 * @property int $id
 * @property string|null $story_id
 * @property string $event
 * @property array<string, mixed>|null $payload
 * @property CarbonImmutable|null $created_at
 */
final class ClientEvent extends Model
{
    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $fillable = ['event', 'payload'];

    /** @return BelongsTo<Story, $this> */
    public function story(): BelongsTo
    {
        return $this->belongsTo(Story::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['payload' => 'array'];
    }
}
