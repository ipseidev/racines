<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\StoresDatesWithOffset;
use App\Enums\QuestionTheme;
use App\Enums\QuestionTone;
use Database\Factories\QuestionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Question éditorialisée du corpus. Sa source est `database/corpus/questions.php`,
 * et `corpus:sync` est le seul chemin qui l'écrit.
 *
 * `text` et `text_tu` sont des gabarits, pas des phrases : ils portent des
 * marqueurs de genre que `QuestionWording` résout. Ne jamais les montrer tels
 * quels.
 *
 * @property string $id
 * @property string $slug
 * @property string $text
 * @property string|null $text_tu
 * @property QuestionTheme $theme
 * @property int $difficulty
 * @property QuestionTone|null $tone
 * @property list<string> $conditions
 * @property list<string> $sensitive_topics
 * @property int $order_hint
 * @property bool $is_active
 * @property string $locale
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class Question extends Model
{
    /** @use HasFactory<QuestionFactory> */
    use HasFactory, HasUuids, StoresDatesWithOffset;

    /** @var array<string, mixed> */
    protected $attributes = [
        'difficulty' => 1,
        'order_hint' => 0,
        'is_active' => true,
        'locale' => 'fr',
        'conditions' => '[]',
        'sensitive_topics' => '[]',
    ];

    /** @var list<string> */
    protected $fillable = [
        'slug', 'text', 'text_tu', 'theme', 'difficulty', 'tone', 'conditions', 'sensitive_topics',
        'order_hint', 'is_active', 'locale',
    ];

    /** @return HasMany<Story, $this> */
    public function stories(): HasMany
    {
        return $this->hasMany(Story::class);
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'theme' => QuestionTheme::class,
            'difficulty' => 'integer',
            'tone' => QuestionTone::class,
            'conditions' => 'array',
            'sensitive_topics' => 'array',
            'order_hint' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
