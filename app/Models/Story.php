<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AnswerType;
use App\Enums\DeletionRequestedBy;
use App\Enums\ShareDecision;
use App\Enums\StoryVisibility;
use App\Enums\ValidatedVia;
use App\States\Story\InBook;
use App\States\Story\Shared;
use App\States\Story\StoryState;
use Carbon\CarbonImmutable;
use Database\Factories\StoryFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\ModelStates\HasStates;

/**
 * Une histoire : une question posée, une réponse du narrateur, un état.
 *
 * L'état ne s'écrit jamais directement : il passe par les transitions de
 * `App\States\Story\Transitions` (bloc 02 §8). `state` est volontairement
 * absent de `$fillable`.
 *
 * @property string $id
 * @property string $project_id
 * @property string $narrator_id
 * @property string|null $question_id
 * @property string|null $custom_question_text
 * @property int $sequence
 * @property StoryState $state
 * @property string|null $previous_state
 * @property CarbonImmutable|null $proposed_at
 * @property CarbonImmutable|null $recorded_at
 * @property CarbonImmutable|null $transcribed_at
 * @property CarbonImmutable|null $validated_at
 * @property CarbonImmutable|null $shared_at
 * @property ValidatedVia|null $validated_via
 * @property ShareDecision|null $share_decision
 * @property CarbonImmutable|null $share_decided_at
 * @property StoryVisibility $visibility
 * @property AnswerType|null $answer_type
 * @property string|null $written_answer
 * @property string|null $title
 * @property CarbonImmutable|null $hidden_at
 * @property CarbonImmutable|null $archived_at
 * @property CarbonImmutable|null $trashed_at
 * @property CarbonImmutable|null $deleted_at
 * @property DeletionRequestedBy|null $deletion_requested_by
 * @property bool $printed_in_book
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
final class Story extends Model
{
    /** @use HasFactory<StoryFactory> */
    use HasFactory, HasStates, HasUuids;

    /** @var list<string> */
    protected $fillable = [
        'question_id', 'custom_question_text', 'sequence', 'proposed_at',
        'share_decision', 'share_decided_at', 'visibility', 'answer_type',
        'written_answer', 'title',
    ];

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return BelongsTo<Narrator, $this> */
    public function narrator(): BelongsTo
    {
        return $this->belongsTo(Narrator::class);
    }

    /** @return BelongsTo<Question, $this> */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    /**
     * Seule source de vérité de la visibilité côté proches (bloc 02 §8).
     *
     * Une histoire « livre uniquement » est incluse au livre sans jamais être
     * écoutable en ligne : le narrateur a choisi le papier, pas la diffusion.
     */
    public function isVisibleToFamily(): bool
    {
        if (! $this->state instanceof Shared && ! $this->state instanceof InBook) {
            return false;
        }

        return $this->visibility !== StoryVisibility::BookOnly;
    }

    /**
     * Classe de l'état d'avant un retrait, pour savoir où l'on peut revenir.
     *
     * @return class-string<StoryState>|null
     */
    public function previousStateClass(): ?string
    {
        if ($this->previous_state === null) {
            return null;
        }

        $resolved = StoryState::resolveStateClass($this->previous_state);

        return $resolved !== null && is_subclass_of($resolved, StoryState::class) ? $resolved : null;
    }

    public function questionText(): ?string
    {
        return $this->custom_question_text ?? $this->question?->text;
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'state' => StoryState::class,
            'sequence' => 'integer',
            'validated_via' => ValidatedVia::class,
            'share_decision' => ShareDecision::class,
            'visibility' => StoryVisibility::class,
            'answer_type' => AnswerType::class,
            'deletion_requested_by' => DeletionRequestedBy::class,
            'printed_in_book' => 'boolean',
            'proposed_at' => 'immutable_datetime',
            'recorded_at' => 'immutable_datetime',
            'transcribed_at' => 'immutable_datetime',
            'validated_at' => 'immutable_datetime',
            'shared_at' => 'immutable_datetime',
            'share_decided_at' => 'immutable_datetime',
            'hidden_at' => 'immutable_datetime',
            'archived_at' => 'immutable_datetime',
            'trashed_at' => 'immutable_datetime',
            'deleted_at' => 'immutable_datetime',
        ];
    }
}
