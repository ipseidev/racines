<?php

declare(strict_types=1);

use App\Actions\IssueRecordToken;
use App\Enums\ShareDecision;
use App\Enums\StoryVisibility;
use App\Enums\TokenType;
use App\Enums\ValidatedVia;
use App\Enums\ValidationVariant;
use App\Events\TranscriptionReady;
use App\Models\AccessToken;
use App\Models\Project;
use App\Models\Story;
use App\Notifications\ReviewReadyNotification;
use App\States\Story\Shared;
use App\States\Story\ToReview;
use App\States\Story\Transcribed;
use App\States\Story\Validated;
use Illuminate\Support\Facades\Notification;

/**
 * Une histoire transcrite, avec le lien d'enregistrement encore vivant : la
 * relecture se fait sur ce même jeton (bloc 07 §6.3).
 */
function transcribedWith(ValidationVariant $variant, ?ShareDecision $decision = null): Story
{
    $project = Project::factory()->create(['validation_variant' => $variant]);
    $story = Story::factory()->forProject($project)->transcribed()->create([
        'share_decision' => $decision,
        'share_decided_at' => $decision === null ? null : now(),
    ]);

    app(IssueRecordToken::class)->handle($story);

    return $story;
}

beforeEach(function (): void {
    Notification::fake();
});

describe('variante A — décision en fin d’enregistrement', function (): void {
    it('partage l’histoire quand le narrateur l’a demandé', function (): void {
        $story = transcribedWith(ValidationVariant::Immediate, ShareDecision::Share);

        TranscriptionReady::dispatch($story, true);

        $story->refresh();

        expect($story->state)->toBeInstanceOf(Shared::class)
            ->and($story->validated_at)->not->toBeNull()
            ->and($story->validated_via)->toBe(ValidatedVia::RecordingEnd)
            ->and($story->shared_at)->not->toBeNull()
            ->and($story->isVisibleToFamily())->toBeTrue();
    });

    it('ne fait rien quand le narrateur garde l’histoire pour lui', function (): void {
        $story = transcribedWith(ValidationVariant::Immediate, ShareDecision::KeepPrivate);

        TranscriptionReady::dispatch($story, true);

        $story->refresh();

        // Rien : ni validation, ni relecture, ni notification. Le narrateur a
        // déjà répondu, on ne le relance pas.
        expect($story->state)->toBeInstanceOf(Transcribed::class)
            ->and($story->validated_at)->toBeNull()
            ->and($story->isVisibleToFamily())->toBeFalse();

        Notification::assertNothingSent();
    });

    it('demande une relecture quand le narrateur a remis son choix à plus tard', function (): void {
        $story = transcribedWith(ValidationVariant::Immediate, ShareDecision::DecideLater);

        TranscriptionReady::dispatch($story, true);

        expect($story->refresh()->state)->toBeInstanceOf(ToReview::class);

        Notification::assertSentTo(
            $story->narrator,
            ReviewReadyNotification::class,
            fn (ReviewReadyNotification $notification): bool => $notification->reason === 'decide_later',
        );
    });

    it('ne partage jamais en l’absence de décision explicite', function (): void {
        $story = transcribedWith(ValidationVariant::Immediate);

        TranscriptionReady::dispatch($story, true);

        $story->refresh();

        // Le silence du narrateur n'est pas un accord : on lui demande.
        expect($story->state)->toBeInstanceOf(ToReview::class)
            ->and($story->isVisibleToFamily())->toBeFalse();

        Notification::assertSentTo(
            $story->narrator,
            ReviewReadyNotification::class,
            fn (ReviewReadyNotification $notification): bool => $notification->reason === 'ready',
        );
    });
});

describe('variante B — relecture d’abord', function (): void {
    it('demande toujours une relecture, même quand le narrateur avait dit « partager »', function (): void {
        $story = transcribedWith(ValidationVariant::Deferred, ShareDecision::Share);

        TranscriptionReady::dispatch($story, true);

        // La variante B ne pose pas la question à l'enregistrement : une
        // décision arrivée d'ailleurs ne court-circuite pas la relecture.
        expect($story->refresh()->state)->toBeInstanceOf(ToReview::class);

        Notification::assertSentTo(
            $story->narrator,
            ReviewReadyNotification::class,
            fn (ReviewReadyNotification $notification): bool => $notification->reason === 'ready',
        );
    });

    it('envoie un lien de relecture qui fonctionne', function (): void {
        $story = transcribedWith(ValidationVariant::Deferred);

        TranscriptionReady::dispatch($story, true);

        $sent = null;
        Notification::assertSentTo($story->narrator, ReviewReadyNotification::class, function ($notification) use (&$sent): bool {
            $sent = $notification;

            return true;
        });

        expect($sent?->reviewUrl())->toContain('/r/')->toContain('/review');
    });
});

it('n’applique rien deux fois si l’événement est rejoué', function (): void {
    $story = transcribedWith(ValidationVariant::Immediate, ShareDecision::Share);

    TranscriptionReady::dispatch($story, true);
    $sharedAt = $story->refresh()->shared_at;

    TranscriptionReady::dispatch($story->refresh(), true);

    // Une file rejoue : l'écouteur doit être sans effet la seconde fois,
    // sinon `shared_at` bougerait et le fil famille se réordonnerait.
    expect($story->refresh()->shared_at?->toIso8601String())->toBe($sharedAt?->toIso8601String());
});

it('ferme le lien d’enregistrement quand l’histoire est validée', function (): void {
    $story = transcribedWith(ValidationVariant::Immediate, ShareDecision::Share);

    TranscriptionReady::dispatch($story, true);

    // Le lien qui traîne dans un SMS ne doit plus permettre de réenregistrer
    // par-dessus une histoire validée (glossaire §4).
    expect(AccessToken::query()
        ->where('subject_id', $story->id)
        ->where('type', TokenType::Record->value)
        ->whereNull('revoked_at')
        ->count())->toBe(0);
});

it('applique la décision même quand la mise au propre a été refusée', function (): void {
    $story = transcribedWith(ValidationVariant::Immediate, ShareDecision::Share);

    // Le verbatim suffit : un refus du modèle ne prive pas la famille du
    // récit, et ne prive pas le narrateur de sa décision.
    TranscriptionReady::dispatch($story, false);

    expect($story->refresh()->state)->toBeInstanceOf(Shared::class);
});

it('respecte le choix « pour le livre seulement » sans partager en ligne', function (): void {
    $story = transcribedWith(ValidationVariant::Immediate, ShareDecision::Share);
    $story->forceFill(['visibility' => StoryVisibility::BookOnly])->save();

    TranscriptionReady::dispatch($story, true);

    $story->refresh();

    // Validée, donc imprimable ; jamais partagée, donc inécoutable en ligne.
    expect($story->state)->toBeInstanceOf(Validated::class)
        ->and($story->validated_at)->not->toBeNull()
        ->and($story->isVisibleToFamily())->toBeFalse();
});
