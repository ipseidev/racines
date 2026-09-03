<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\StoresDatesWithOffset;
use App\Enums\BookFormat;
use App\Enums\BookStatus;
use Carbon\CarbonImmutable;
use Database\Factories\BookFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Le livre d'un projet — un seul, quel qu'en soit le format.
 *
 * @property string $id
 * @property string $project_id
 * @property string $template
 * @property BookFormat $format
 * @property BookStatus $status
 * @property BookFormat|null $proposed_format
 * @property int $page_count_estimate
 * @property CarbonImmutable|null $book_ready_at
 * @property string|null $foreword
 * @property string $text_version_policy
 * @property string|null $proof_pdf_path
 * @property int $proof_version
 * @property CarbonImmutable|null $proof_generated_at
 * @property CarbonImmutable|null $proof_approved_at
 * @property int|null $proof_approved_by_user_id
 * @property bool $proof_acknowledged_final_print
 * @property bool $proof_acknowledged_lexicon_reviewed
 * @property string|null $print_order_ref
 * @property CarbonImmutable|null $ordered_at
 * @property CarbonImmutable|null $printed_at
 * @property CarbonImmutable|null $delivered_at
 * @property CarbonImmutable|null $extension_granted_at
 * @property CarbonImmutable|null $print_credit_expires_at
 * @property-read Project $project
 * @property-read Collection<int, BookChapter> $chapters
 */
final class Book extends Model
{
    /** @use HasFactory<BookFactory> */
    use HasFactory, HasUuids, StoresDatesWithOffset;

    /**
     * Les défauts, posés sur le modèle et pas seulement en base.
     *
     * Sans eux, une instance neuve — celle que rend `firstOrNew` — a un
     * `status` nul, et tout appel de méthode dessus casse. Les défauts de
     * colonne ne s'appliquent qu'à l'insertion, ce qui est trop tard pour du
     * code qui décide avant d'enregistrer.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'template' => 'classic',
        'format' => 'founding_chapter',
        'status' => 'draft',
        'page_count_estimate' => 0,
        'text_version_policy' => 'edited_or_fluide',
        'proof_version' => 0,
        'proof_acknowledged_final_print' => false,
        'proof_acknowledged_lexicon_reviewed' => false,
    ];

    /** @var list<string> */
    protected $fillable = [
        'template', 'format', 'status', 'proposed_format', 'page_count_estimate',
        'book_ready_at', 'foreword', 'text_version_policy',
    ];

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return HasMany<BookChapter, $this> */
    public function chapters(): HasMany
    {
        return $this->hasMany(BookChapter::class)->orderBy('position');
    }

    /**
     * Les deux accords sont-ils donnés ?
     *
     * Séparés et tous deux obligatoires : le premier engage sur
     * l'irréversible, le second dit que les noms propres ont été relus —
     * c'est la faute la plus fréquente et la plus blessante d'un livre de
     * famille.
     */
    public function isApprovable(): bool
    {
        return $this->proof_pdf_path !== null
            && $this->proof_acknowledged_final_print
            && $this->proof_acknowledged_lexicon_reviewed;
    }

    /**
     * La sélection est-elle encore modifiable ?
     *
     * Non après l'accord : ce que la famille a approuvé est ce qui sera
     * imprimé, y compris les erreurs qu'elle n'aurait pas vues. Laisser la
     * sélection ouverte après coup rendrait cet accord vide de sens.
     */
    public function isEditable(): bool
    {
        return ! $this->status->isLocked();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'format' => BookFormat::class,
            'proposed_format' => BookFormat::class,
            'status' => BookStatus::class,
            'page_count_estimate' => 'integer',
            'proof_version' => 'integer',
            'proof_acknowledged_final_print' => 'boolean',
            'proof_acknowledged_lexicon_reviewed' => 'boolean',
            'book_ready_at' => 'immutable_datetime',
            'proof_generated_at' => 'immutable_datetime',
            'proof_approved_at' => 'immutable_datetime',
            'ordered_at' => 'immutable_datetime',
            'printed_at' => 'immutable_datetime',
            'delivered_at' => 'immutable_datetime',
            'extension_granted_at' => 'immutable_datetime',
            'print_credit_expires_at' => 'immutable_datetime',
        ];
    }
}
