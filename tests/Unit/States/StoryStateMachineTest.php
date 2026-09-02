<?php

declare(strict_types=1);

use App\Enums\ConsentKind;
use App\Enums\DeletionRequestedBy;
use App\Enums\ShareDecision;
use App\Enums\StoryVisibility;
use App\Enums\ValidatedVia;
use App\Exceptions\Domain\ForbiddenTransition;
use App\Models\Consent;
use App\Models\Story;
use App\Models\User;
use App\States\Story\Archived;
use App\States\Story\Deleted;
use App\States\Story\Hidden;
use App\States\Story\InBook;
use App\States\Story\Proposed;
use App\States\Story\Recorded;
use App\States\Story\Shared;
use App\States\Story\StoryState;
use App\States\Story\ToReview;
use App\States\Story\Transcribed;
use App\States\Story\Trashed;
use App\States\Story\Validated;

/**
 * Une assertion par cellule de la matrice R-4. La souveraineté du narrateur
 * n'est pas un effort d'écran : c'est une propriété de cette machine.
 */
it('starts in proposed', function (): void {
    $story = Story::factory()->create();

    expect($story->state)->toBeInstanceOf(Proposed::class)
        ->and($story->refresh()->getRawOriginal('state'))->toBe('proposed');
});

it('moves proposed → recorded', function (): void {
    $story = Story::factory()->proposed()->create();

    $story->state->transitionTo(Recorded::class);

    expect($story->refresh()->state)->toBeInstanceOf(Recorded::class)
        ->and($story->recorded_at)->not->toBeNull();
});

it('moves recorded → transcribed', function (): void {
    $story = Story::factory()->recorded()->create();

    $story->state->transitionTo(Transcribed::class);

    expect($story->refresh()->state)->toBeInstanceOf(Transcribed::class)
        ->and($story->transcribed_at)->not->toBeNull();
});

it('moves transcribed → to_review', function (): void {
    $story = Story::factory()->transcribed()->create();

    $story->state->transitionTo(ToReview::class);

    expect($story->refresh()->state)->toBeInstanceOf(ToReview::class);
});

it('moves to_review → validated', function (): void {
    $story = Story::factory()->toReview()->create();

    $story->state->transitionTo(Validated::class);

    expect($story->refresh()->state)->toBeInstanceOf(Validated::class);
});

it('moves validated → shared', function (): void {
    $story = Story::factory()->validated()->create();

    $story->state->transitionTo(Shared::class);

    expect($story->refresh()->state)->toBeInstanceOf(Shared::class)
        ->and($story->shared_at)->not->toBeNull();
});

it('moves shared → in_book', function (): void {
    $story = Story::factory()->shared()->create();

    $story->state->transitionTo(InBook::class);

    expect($story->refresh()->state)->toBeInstanceOf(InBook::class);
});

it('moves validated → in_book when visibility is book_only', function (): void {
    $story = Story::factory()->validated()->bookOnly()->create();

    $story->state->transitionTo(InBook::class);

    expect($story->refresh()->state)->toBeInstanceOf(InBook::class)
        ->and($story->isVisibleToFamily())->toBeFalse();
});

it('refuses validated → in_book when the story is not reserved for the book', function (): void {
    $story = Story::factory()->validated()->create();

    expect(fn () => $story->state->transitionTo(InBook::class))->toThrow(ForbiddenTransition::class);
});

it('refuses validated → shared when the story is reserved for the book', function (): void {
    $story = Story::factory()->validated()->bookOnly()->create();

    expect(fn () => $story->state->transitionTo(Shared::class))->toThrow(ForbiddenTransition::class);
});

it('moves transcribed → validated only when share_decision is share', function (): void {
    $story = Story::factory()->transcribed()->create([
        'share_decision' => ShareDecision::Share,
        'share_decided_at' => now(),
    ]);

    $story->state->transitionTo(Validated::class);

    expect($story->refresh()->state)->toBeInstanceOf(Validated::class)
        ->and($story->validated_via)->toBe(ValidatedVia::RecordingEnd);
});

it('refuses transcribed → validated without share decision', function (): void {
    $story = Story::factory()->transcribed()->create();

    expect(fn () => $story->state->transitionTo(Validated::class))->toThrow(ForbiddenTransition::class);

    expect($story->refresh()->state)->toBeInstanceOf(Transcribed::class)
        ->and($story->validated_at)->toBeNull();
});

it('refuses transcribed → validated when the narrator asked to keep the story private', function (): void {
    $story = Story::factory()->transcribed()->create([
        'share_decision' => ShareDecision::KeepPrivate,
        'share_decided_at' => now(),
    ]);

    expect(fn () => $story->state->transitionTo(Validated::class))->toThrow(ForbiddenTransition::class);
});

it('refuses to_review → shared directly', function (): void {
    $story = Story::factory()->toReview()->create();

    expect(fn () => $story->state->transitionTo(Shared::class))->toThrow(ForbiddenTransition::class);
});

it('refuses proposed → validated', function (): void {
    $story = Story::factory()->proposed()->create();

    expect(fn () => $story->state->transitionTo(Validated::class))->toThrow(ForbiddenTransition::class);
});

it('moves recorded → validated only via phone_operator with an oral consent', function (): void {
    $operator = User::factory()->support()->create();
    $story = Story::factory()->recorded()->create();

    Consent::factory()
        ->byPhoneOperator($operator->id)
        ->create([
            'subject_id' => $story->narrator_id,
            'project_id' => $story->project_id,
        ]);

    $story->state->transitionTo(Validated::class, ValidatedVia::PhoneOperator);

    expect($story->refresh()->state)->toBeInstanceOf(Validated::class)
        ->and($story->validated_via)->toBe(ValidatedVia::PhoneOperator)
        ->and($story->narrator->hasConsent(ConsentKind::PhoneCallRecording))->toBeTrue();
});

it('refuses recorded → validated without an oral consent on file', function (): void {
    $story = Story::factory()->recorded()->create();

    expect(fn () => $story->state->transitionTo(Validated::class, ValidatedVia::PhoneOperator))
        ->toThrow(ForbiddenTransition::class);
});

it('refuses recorded → validated when no validation path is given', function (): void {
    $story = Story::factory()->recorded()->create();

    expect(fn () => $story->state->transitionTo(Validated::class))->toThrow(ForbiddenTransition::class);
});

it('moves any state ≥ recorded → hidden and back to previous state', function (): void {
    foreach (['recorded', 'transcribed', 'to_review', 'validated', 'shared', 'in_book'] as $from) {
        $story = Story::factory()->create(['state' => $from]);

        $story->state->transitionTo(Hidden::class);

        expect($story->refresh()->state)->toBeInstanceOf(Hidden::class)
            ->and($story->previous_state)->toBe($from);

        $story->state->transitionTo(StoryState::resolveStateClass($from));

        expect($story->refresh()->getRawOriginal('state'))->toBe($from)
            ->and($story->previous_state)->toBeNull()
            ->and($story->hidden_at)->toBeNull();
    }
});

it('records previous_state when hiding', function (): void {
    $story = Story::factory()->shared()->create();

    $story->state->transitionTo(Hidden::class);

    expect($story->refresh()->previous_state)->toBe('shared')
        ->and($story->hidden_at)->not->toBeNull()
        ->and($story->previousStateClass())->toBe(Shared::class)
        ->and($story->isVisibleToFamily())->toBeFalse();
});

it('refuses hidden → shared without going back to validated', function (): void {
    $story = Story::factory()->hidden('transcribed')->create();

    expect(fn () => $story->state->transitionTo(Shared::class))->toThrow(ForbiddenTransition::class);

    expect($story->refresh()->state)->toBeInstanceOf(Hidden::class);
});

it('moves any state ≥ recorded → archived', function (): void {
    foreach (['recorded', 'transcribed', 'to_review', 'validated', 'shared', 'in_book'] as $from) {
        $story = Story::factory()->create(['state' => $from]);

        $story->state->transitionTo(Archived::class);

        expect($story->refresh()->state)->toBeInstanceOf(Archived::class)
            ->and($story->previous_state)->toBe($from)
            ->and($story->archived_at)->not->toBeNull();
    }
});

it('moves any state ≥ recorded → trashed', function (): void {
    foreach (['recorded', 'transcribed', 'to_review', 'validated', 'shared', 'in_book'] as $from) {
        $story = Story::factory()->create(['state' => $from]);

        $story->state->transitionTo(Trashed::class);

        expect($story->refresh()->state)->toBeInstanceOf(Trashed::class)
            ->and($story->previous_state)->toBe($from)
            ->and($story->trashed_at)->not->toBeNull();
    }
});

it('refuses hiding or trashing a story that is merely proposed', function (): void {
    $story = Story::factory()->proposed()->create();

    expect(fn () => $story->state->transitionTo(Hidden::class))->toThrow(ForbiddenTransition::class)
        ->and(fn () => $story->state->transitionTo(Trashed::class))->toThrow(ForbiddenTransition::class);
});

it('restores a trashed story inside the thirty-day window', function (): void {
    $story = Story::factory()->trashed('validated')->create();

    $this->travel(29)->days();

    $story->state->transitionTo(Validated::class);

    expect($story->refresh()->state)->toBeInstanceOf(Validated::class)
        ->and($story->trashed_at)->toBeNull();
});

it('refuses to restore a trashed story once the thirty-day window has closed', function (): void {
    $story = Story::factory()->trashed('validated')->create();

    $this->travel(31)->days();

    expect(fn () => $story->state->transitionTo(Validated::class))->toThrow(ForbiddenTransition::class);
});

it('moves trashed → deleted and refuses deleted → anything', function (): void {
    $story = Story::factory()->trashed()->create();

    $story->state->transitionTo(Deleted::class, DeletionRequestedBy::Narrator);

    expect($story->refresh()->state)->toBeInstanceOf(Deleted::class)
        ->and($story->deleted_at)->not->toBeNull()
        ->and($story->deletion_requested_by)->toBe(DeletionRequestedBy::Narrator);

    foreach ([Trashed::class, Validated::class, Shared::class, Hidden::class, Recorded::class] as $target) {
        expect(fn () => $story->state->transitionTo($target))->toThrow(ForbiddenTransition::class);
    }
});

it('records validated_at and validated_via on validation', function (): void {
    $this->freezeTime();

    $story = Story::factory()->toReview()->create();

    $story->state->transitionTo(Validated::class);
    $story->refresh();

    // Postgres rend un `timestamptz` en UTC, l'application vit à Paris : on
    // compare des instants, pas des représentations.
    expect($story->validated_at?->getTimestamp())->toBe(now()->getTimestamp())
        ->and($story->validated_via)->toBe(ValidatedVia::PostTranscription);
});

it('never exposes a story to the family before it is shared', function (): void {
    foreach (['proposed', 'recorded', 'transcribed', 'to_review', 'validated'] as $state) {
        expect(Story::factory()->create(['state' => $state])->isVisibleToFamily())
            ->toBeFalse("un état {$state} ne doit rien montrer aux proches");
    }

    expect(Story::factory()->shared()->create()->isVisibleToFamily())->toBeTrue()
        ->and(Story::factory()->inBook()->create()->isVisibleToFamily())->toBeTrue()
        ->and(Story::factory()->shared()->create(['visibility' => StoryVisibility::BookOnly])->isVisibleToFamily())->toBeFalse();
});
