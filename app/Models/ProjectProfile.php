<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\StoresDatesWithOffset;
use App\Enums\BuyerFocus;
use App\Enums\BuyerRelation;
use App\Enums\GrammaticalGender;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Ce que la famille a dit de la narratrice, pour choisir ses questions.
 *
 * Rempli par le tunnel d'après-achat, qu'on peut passer. Tout est facultatif,
 * et un champ vide vaut « on ne sait pas » : c'est `QuestionProfile` qui dit
 * ce que chaque valeur change à l'envoi.
 *
 * @property string $id
 * @property string $project_id
 * @property array<string, bool> $facts
 * @property list<string> $avoided_topics
 * @property list<string> $favored_themes
 * @property BuyerRelation|null $buyer_relation
 * @property BuyerFocus|null $buyer_focus
 * @property string|null $buyer_first_name
 * @property GrammaticalGender|null $buyer_gender
 * @property CarbonImmutable|null $completed_at
 * @property CarbonImmutable|null $skipped_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class ProjectProfile extends Model
{
    use HasUuids, StoresDatesWithOffset;

    /** @var array<string, mixed> */
    protected $attributes = [
        'facts' => '{}',
        'avoided_topics' => '[]',
        'favored_themes' => '[]',
    ];

    /** @var list<string> */
    protected $fillable = [
        'project_id', 'facts', 'avoided_topics', 'favored_themes',
        'buyer_relation', 'buyer_focus', 'buyer_first_name', 'buyer_gender',
        'completed_at', 'skipped_at',
    ];

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'facts' => 'array',
            'avoided_topics' => 'array',
            'favored_themes' => 'array',
            'buyer_relation' => BuyerRelation::class,
            'buyer_focus' => BuyerFocus::class,
            'buyer_gender' => GrammaticalGender::class,
            'completed_at' => 'immutable_datetime',
            'skipped_at' => 'immutable_datetime',
        ];
    }
}
