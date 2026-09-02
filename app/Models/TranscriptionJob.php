<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\StoresDatesWithOffset;
use App\Enums\TranscriptionStatus;
use Carbon\CarbonImmutable;
use Database\Factories\TranscriptionJobFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Une demande de transcription auprès d'un fournisseur. Table interne.
 *
 * @property int $id
 * @property string $recording_id
 * @property string $provider
 * @property string|null $provider_job_id
 * @property TranscriptionStatus $status
 * @property int $attempts
 * @property CarbonImmutable|null $submitted_at
 * @property CarbonImmutable|null $completed_at
 * @property string|null $error
 * @property-read Recording $recording
 */
final class TranscriptionJob extends Model
{
    /** @use HasFactory<TranscriptionJobFactory> */
    use HasFactory, StoresDatesWithOffset;

    /** @var array<string, mixed> */
    protected $attributes = ['status' => TranscriptionStatus::Queued->value, 'attempts' => 0];

    /** @var list<string> */
    protected $fillable = ['recording_id', 'provider', 'provider_job_id', 'status', 'attempts', 'submitted_at'];

    /** @return BelongsTo<Recording, $this> */
    public function recording(): BelongsTo
    {
        return $this->belongsTo(Recording::class);
    }

    public function markProcessing(?string $providerJobId): void
    {
        $this->provider_job_id = $providerJobId;
        $this->status = TranscriptionStatus::Processing;
        $this->submitted_at = now();
        $this->attempts++;
        $this->save();
    }

    public function markDone(): void
    {
        $this->status = TranscriptionStatus::Done;
        $this->completed_at = now();
        $this->save();
    }

    public function markFailed(string $error): void
    {
        $this->status = TranscriptionStatus::Failed;
        $this->error = mb_substr($error, 0, 2000);
        $this->completed_at = now();
        $this->save();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => TranscriptionStatus::class,
            'attempts' => 'integer',
            'submitted_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
        ];
    }
}
