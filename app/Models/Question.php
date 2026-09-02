<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\QuestionTheme;
use Database\Factories\QuestionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Question éditorialisée du corpus (annexe A). Les données arrivent au bloc 05.
 *
 * @property string $id
 * @property string $slug
 * @property string $text
 * @property QuestionTheme $theme
 * @property int $difficulty
 * @property int $order_hint
 * @property bool $is_active
 * @property string $locale
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class Question extends Model
{
    /** @use HasFactory<QuestionFactory> */
    use HasFactory, HasUuids;

    /** @var array<string, mixed> */
    protected $attributes = [
        'difficulty' => 1,
        'order_hint' => 0,
        'is_active' => true,
        'locale' => 'fr',
    ];

    /** @var list<string> */
    protected $fillable = ['slug', 'text', 'theme', 'difficulty', 'order_hint', 'is_active', 'locale'];

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
            'order_hint' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
