<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\StoresDatesWithOffset;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Ce que l'Initiateur·rice change au corpus pour son projet : exclure une
 * question, en avancer une autre (annexe A, règle 3). Table interne.
 *
 * @property int $id
 * @property string $project_id
 * @property string $question_id
 * @property bool $excluded
 * @property int|null $custom_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class ProjectQuestionSetting extends Model
{
    use StoresDatesWithOffset;

    /** @var array<string, mixed> */
    protected $attributes = ['excluded' => false];

    /** @var list<string> */
    protected $fillable = ['project_id', 'question_id', 'excluded', 'custom_order'];

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return BelongsTo<Question, $this> */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['excluded' => 'boolean', 'custom_order' => 'integer'];
    }
}
