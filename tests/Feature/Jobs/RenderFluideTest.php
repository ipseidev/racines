<?php

declare(strict_types=1);

use App\Enums\ConsentKind;
use App\Enums\TranscriptKind;
use App\Events\TranscriptionReady;
use App\Jobs\RenderFluide;
use App\Models\Consent;
use App\Models\LexiconEntry;
use App\Models\Story;
use App\Models\Transcript;
use App\Services\Llm\FakeStoryRenderer;
use App\Services\Llm\StoryRenderer;
use Illuminate\Support\Facades\Event;

function fakeRenderer(): FakeStoryRenderer
{
    $renderer = new FakeStoryRenderer;
    app()->instance(StoryRenderer::class, $renderer);

    return $renderer;
}

/**
 * Un verbatim dont le narrateur a consenti à la mise au propre.
 */
function verbatimWithConsent(array $storyAttributes = []): Transcript
{
    $story = Story::factory()->transcribed()->create([...$storyAttributes, 'title' => null]);

    Consent::factory()->create([
        'subject_id' => $story->narrator_id,
        'project_id' => $story->project_id,
        'kind' => ConsentKind::AiRendering,
    ]);

    return Transcript::factory()->create(['story_id' => $story->id]);
}

beforeEach(function (): void {
    Event::fake([TranscriptionReady::class]);
    fakeRenderer();
});

it('crée un rendu fluide courant, sans toucher au verbatim', function (): void {
    $verbatim = verbatimWithConsent();

    app()->call([new RenderFluide($verbatim->id), 'handle']);

    $fluide = $verbatim->story->transcripts()->ofKind(TranscriptKind::Fluide)->current()->sole();

    expect($fluide->text)->not->toBe($verbatim->text)
        ->and($fluide->text)->not->toContain('euh')
        ->and($fluide->source_transcript_id)->toBe($verbatim->id)
        ->and($fluide->recording_id)->toBe($verbatim->recording_id)
        ->and($fluide->language)->toBe('fr')
        ->and($verbatim->refresh()->is_current)->toBeTrue()
        ->and($verbatim->text)->toContain('euh');
});

it('pose le titre de l’histoire quand il est vide', function (): void {
    $verbatim = verbatimWithConsent();

    app()->call([new RenderFluide($verbatim->id), 'handle']);

    expect($verbatim->story->refresh()->title)->not->toBeNull()
        ->and(mb_strlen((string) $verbatim->story->title))->toBeLessThanOrEqual(60);
});

it('ne remplace jamais un titre existant', function (): void {
    $verbatim = verbatimWithConsent();
    $verbatim->story->forceFill(['title' => 'Le titre choisi par la famille'])->save();

    app()->call([new RenderFluide($verbatim->id), 'handle']);

    // Un titre posé par un humain gagne toujours contre un titre suggéré.
    expect($verbatim->story->refresh()->title)->toBe('Le titre choisi par la famille');
});

it('émet TranscriptionReady en signalant que le récit est mis au propre', function (): void {
    $verbatim = verbatimWithConsent();

    app()->call([new RenderFluide($verbatim->id), 'handle']);

    Event::assertDispatched(
        TranscriptionReady::class,
        fn (TranscriptionReady $event): bool => $event->story->is($verbatim->story) && $event->rendered,
    );
});

it('laisse le verbatim seul quand le narrateur n’a pas consenti', function (): void {
    $story = Story::factory()->transcribed()->create();
    $verbatim = Transcript::factory()->create(['story_id' => $story->id]);

    app()->call([new RenderFluide($verbatim->id), 'handle']);

    expect($story->transcripts()->ofKind(TranscriptKind::Fluide)->count())->toBe(0);

    // Le narrateur a droit à sa relecture, avec ou sans mise au propre.
    Event::assertDispatched(
        TranscriptionReady::class,
        fn (TranscriptionReady $event): bool => $event->rendered === false,
    );
});

it('laisse le verbatim seul quand le consentement a été retiré', function (): void {
    $verbatim = verbatimWithConsent();
    Consent::factory()->revoked()->create([
        'subject_id' => $verbatim->story->narrator_id,
        'project_id' => $verbatim->story->project_id,
        'kind' => ConsentKind::AiRendering,
    ]);

    app()->call([new RenderFluide($verbatim->id), 'handle']);

    expect($verbatim->story->transcripts()->ofKind(TranscriptKind::Fluide)->count())->toBe(0)
        ->and($verbatim->story->narrator->hasConsent(ConsentKind::AiRendering))->toBeFalse();
});

it('garde le verbatim et n’écrit rien quand le modèle décline', function (): void {
    $verbatim = verbatimWithConsent();
    fakeRenderer()->refusing();

    app()->call([new RenderFluide($verbatim->id), 'handle']);

    // Un refus n'est pas rattrapé par un autre modèle : le récit reste brut.
    expect($verbatim->story->transcripts()->ofKind(TranscriptKind::Fluide)->count())->toBe(0)
        ->and($verbatim->story->refresh()->title)->toBeNull();

    Event::assertDispatched(
        TranscriptionReady::class,
        fn (TranscriptionReady $event): bool => $event->rendered === false,
    );
});

it('passe le lexique du projet au rendu', function (): void {
    $verbatim = verbatimWithConsent();
    LexiconEntry::factory()->create([
        'project_id' => $verbatim->story->project_id,
        'term' => 'Kerhostin',
        'replacement' => null,
    ]);

    app()->call([new RenderFluide($verbatim->id), 'handle']);

    $fluide = $verbatim->story->transcripts()->ofKind(TranscriptKind::Fluide)->sole();

    expect($fluide->metadata['proper_nouns'])->toContain('Kerhostin');
});

it('ne crée pas un second rendu fluide s’il est rejoué', function (): void {
    $verbatim = verbatimWithConsent();

    app()->call([new RenderFluide($verbatim->id), 'handle']);
    app()->call([new RenderFluide($verbatim->id), 'handle']);

    expect($verbatim->story->transcripts()->ofKind(TranscriptKind::Fluide)->count())->toBe(1);
});

it('ignore un transcript qui n’est pas un verbatim', function (): void {
    $verbatim = verbatimWithConsent();
    $fluide = Transcript::factory()->fluide()->create(['story_id' => $verbatim->story_id]);

    app()->call([new RenderFluide($fluide->id), 'handle']);

    expect($verbatim->story->transcripts()->ofKind(TranscriptKind::Fluide)->count())->toBe(1);
    Event::assertNothingDispatched();
});

it('consigne les thèmes et les signalements sensibles dans les métadonnées', function (): void {
    $verbatim = verbatimWithConsent();

    app()->call([new RenderFluide($verbatim->id), 'handle']);

    $metadata = $verbatim->story->transcripts()->ofKind(TranscriptKind::Fluide)->sole()->metadata;

    expect($metadata)->toHaveKeys(['themes', 'proper_nouns', 'sensitive_flags', 'provider', 'model'])
        ->and($metadata['sensitive_flags'])->toBe([]);
});
