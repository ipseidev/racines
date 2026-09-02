<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\StoresDatesWithOffset;
use App\Enums\CohortPhase;
use Database\Factories\CohortFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Cohorte du pilote (bloc 17). Sert à comparer des vagues entre elles sans
 * mélanger les mesures d'hypothèses.
 *
 * @property string $id
 * @property string $name
 * @property CohortPhase $phase
 * @property Carbon|null $started_at
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class Cohort extends Model
{
    /** @use HasFactory<CohortFactory> */
    use HasFactory, HasUuids, StoresDatesWithOffset;

    /** @var list<string> */
    protected $fillable = ['name', 'phase', 'started_at', 'notes'];

    /** @return HasMany<Project, $this> */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'phase' => CohortPhase::class,
            'started_at' => 'immutable_datetime',
        ];
    }
}
