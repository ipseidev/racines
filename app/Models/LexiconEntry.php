<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\StoresDatesWithOffset;
use Database\Factories\LexiconEntryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Un nom propre du projet et sa graphie.
 *
 * @property int $id
 * @property string $project_id
 * @property string $term
 * @property string|null $replacement
 * @property string|null $notes
 * @property string|null $created_by_type
 * @property string|null $created_by_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class LexiconEntry extends Model
{
    /** @use HasFactory<LexiconEntryFactory> */
    use HasFactory, StoresDatesWithOffset;

    /** @var list<string> */
    protected $fillable = ['term', 'replacement', 'notes'];

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Graphie attendue : le remplacement s'il existe, sinon le terme lui-même
     * — un nom peut être au lexique juste pour que l'ASR l'entende.
     */
    public function spelling(): string
    {
        return $this->replacement ?? $this->term;
    }
}
