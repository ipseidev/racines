<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\StoresDatesWithOffset;
use App\States\Story\ToReview;
use Carbon\CarbonImmutable;
use Database\Factories\MandateFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Un mandat de validation, consenti par le narrateur et révocable par lui.
 *
 * @property string $id
 * @property string $project_id
 * @property string $narrator_id
 * @property string $holder_type
 * @property string $holder_id
 * @property list<string> $scope
 * @property string $consent_id
 * @property CarbonImmutable $granted_at
 * @property CarbonImmutable|null $revoked_at
 * @property-read Narrator $narrator
 * @property-read Consent $consent
 */
final class Mandate extends Model
{
    /** @use HasFactory<MandateFactory> */
    use HasFactory, HasUuids, StoresDatesWithOffset;

    /** @var list<string> */
    protected $fillable = ['scope', 'granted_at'];

    /** @return BelongsTo<Narrator, $this> */
    public function narrator(): BelongsTo
    {
        return $this->belongsTo(Narrator::class);
    }

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return BelongsTo<Consent, $this> */
    public function consent(): BelongsTo
    {
        return $this->belongsTo(Consent::class);
    }

    /** @return MorphTo<Model, $this> */
    public function holder(): MorphTo
    {
        return $this->morphTo();
    }

    public function isLive(): bool
    {
        return $this->revoked_at === null;
    }

    /**
     * Ce mandat autorise-t-il cet acte, sur cette histoire ?
     *
     * Quatre conditions, et aucune n'est superflue : le mandat vit encore,
     * l'acte est dans son périmètre, l'histoire est celle de *son* narrateur,
     * et elle attend une relecture. Le mandat sert à débloquer une relecture
     * que le narrateur ne fait pas ; il ne remplace pas sa décision de
     * partage en fin d'enregistrement, ni aucun retrait.
     */
    public function covers(Story $story, string $act): bool
    {
        if (! $this->isLive()) {
            return false;
        }

        if (! in_array($act, $this->scope, true)) {
            return false;
        }

        if ($story->narrator_id !== $this->narrator_id) {
            return false;
        }

        return $story->state instanceof ToReview;
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'scope' => 'array',
            'granted_at' => 'immutable_datetime',
            'revoked_at' => 'immutable_datetime',
            'holder_id' => 'string',
        ];
    }
}
