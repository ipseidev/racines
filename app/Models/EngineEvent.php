<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\StoresDatesWithOffset;
use App\Enums\EngineOutcome;
use App\Enums\EngineRuleId;
use Carbon\CarbonImmutable;
use Database\Factories\EngineEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un déclenchement du moteur.
 *
 * @property int $id
 * @property string $project_id
 * @property string|null $story_id
 * @property EngineRuleId $rule_id
 * @property string $occurrence_key
 * @property string $dedupe_key
 * @property CarbonImmutable $fired_at
 * @property array<string, mixed>|null $action_taken
 * @property EngineOutcome|null $outcome
 * @property CarbonImmutable|null $outcome_at
 * @property-read Project $project
 * @property-read Story|null $story
 */
final class EngineEvent extends Model
{
    /** @use HasFactory<EngineEventFactory> */
    use HasFactory, StoresDatesWithOffset;

    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $fillable = ['rule_id', 'occurrence_key', 'dedupe_key', 'fired_at', 'action_taken'];

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

    /**
     * Le déclenchement a-t-il été supprimé au profit d'une règle plus
     * prioritaire ? On l'enregistre quand même : savoir qu'une règle *aurait*
     * parlé fait partie de la mesure.
     */
    public function wasSuppressed(): bool
    {
        return isset($this->action_taken['suppressed_by']);
    }

    /**
     * Le numéro de tentative, lu dans la clé d'occurrence.
     */
    public function attempt(): int
    {
        $parts = explode(':', $this->occurrence_key);

        return (int) (end($parts) ?: 1);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'rule_id' => EngineRuleId::class,
            'outcome' => EngineOutcome::class,
            'action_taken' => 'array',
            'fired_at' => 'immutable_datetime',
            'outcome_at' => 'immutable_datetime',
        ];
    }
}
