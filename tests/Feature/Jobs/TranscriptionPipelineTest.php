<?php

declare(strict_types=1);

use App\Actions\AddLexiconEntry;
use App\Actions\StoreVerbatimTranscript;
use App\Enums\ConsentKind;
use App\Enums\TranscriptionStatus;
use App\Enums\TranscriptKind;
use App\Events\TranscriptionReady;
use App\Jobs\PollTranscription;
use App\Jobs\RenderFluide;
use App\Jobs\SubmitTranscription;
use App\Jobs\TranscodeRecording;
use App\Models\Consent;
use App\Models\OutboundMessage;
use App\Models\Recording;
use App\Models\Story;
use App\Models\Transcript;
use App\Models\TranscriptionJob;
use App\Services\Llm\FakeStoryRenderer;
use App\Services\Llm\StoryRenderer;
use App\Services\Storage\MediaStorage;
use App\Services\Transcription\FakeTranscriptionProvider;
use App\Services\Transcription\TranscriptionProvider;
use App\States\Story\Transcribed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

function fakeAsr(): FakeTranscriptionProvider
{
    $provider = new FakeTranscriptionProvider;
    app()->instance(TranscriptionProvider::class, $provider);

    return $provider;
}

function confirmedRecording(): Recording
{
    $story = Story::factory()->recorded()->create();
    $recording = Recording::factory()->confirmed()->create(['story_id' => $story->id]);

    // Le narrateur a consenti à la mise au propre : c'est le cas nominal.
    Consent::factory()->create([
        'subject_id' => $story->narrator_id,
        'project_id' => $story->project_id,
        'kind' => ConsentKind::AiRendering,
    ]);

    $storage = fakeMediaStorage();
    $storage->put((string) $recording->original_path, 'audio');

    return $recording;
}

it('soumet, stocke le verbatim, fait passer l’histoire en transcrite et demande la mise au propre', function (): void {
    Queue::fake();
    fakeAsr();

    $recording = confirmedRecording();

    app(SubmitTranscription::class, ['recordingId' => $recording->id]);
    (new SubmitTranscription($recording->id))->handle(
        app(TranscriptionProvider::class),
        app(MediaStorage::class),
        app(StoreVerbatimTranscript::class),
    );

    $transcript = Transcript::query()->sole();

    expect($transcript->kind)->toBe(TranscriptKind::Verbatim)
        ->and($transcript->version)->toBe(1)
        ->and($transcript->provider)->toBe('fake')
        ->and($transcript->words)->not->toBeEmpty()
        ->and($recording->story->refresh()->state)->toBeInstanceOf(Transcribed::class)
        ->and(TranscriptionJob::query()->sole()->status)->toBe(TranscriptionStatus::Done);

    Queue::assertPushed(RenderFluide::class);
});

it('donne le lexique du projet au fournisseur, avant la transcription', function (): void {
    Queue::fake();
    $provider = fakeAsr();

    $recording = confirmedRecording();
    $project = $recording->story->project;

    app(AddLexiconEntry::class)->handle($project, 'Ker Austin', 'Kerhostin', $project->owner);

    (new SubmitTranscription($recording->id))->handle(
        $provider,
        app(MediaStorage::class),
        app(StoreVerbatimTranscript::class),
    );

    // Donné d'avance, le nom sort juste ; donné après, il faut le corriger.
    expect($provider->requestFor($recording->id)?->vocabulary)->toBe(['Kerhostin']);
});

it('corrige le texte avec le lexique, sans toucher aux mots horodatés', function (): void {
    Queue::fake();
    $provider = fakeAsr();

    $recording = confirmedRecording();
    $project = $recording->story->project;

    app(AddLexiconEntry::class)->handle($project, 'Kerhostin', 'Kerhostin-en-Saint-Pierre', $project->owner);

    (new SubmitTranscription($recording->id))->handle(
        $provider,
        app(MediaStorage::class),
        app(StoreVerbatimTranscript::class),
    );

    $transcript = Transcript::query()->sole();

    expect($transcript->text)->toContain('Kerhostin-en-Saint-Pierre');

    // Les mots servent à suivre l'audio : les corriger décalerait le suivi.
    $words = collect($transcript->words)->pluck('word')->implode(' ');

    expect($words)->toContain('Kerhostin,')
        ->and($words)->not->toContain('Saint-Pierre');
});

it('interroge les travaux en cours dont le rappel n’est pas arrivé', function (): void {
    Queue::fake();
    $provider = fakeAsr()->processingFor(1);

    $recording = confirmedRecording();

    (new SubmitTranscription($recording->id))->handle(
        $provider,
        app(MediaStorage::class),
        app(StoreVerbatimTranscript::class),
    );

    expect(Transcript::query()->count())->toBe(0)
        ->and(TranscriptionJob::query()->sole()->status)->toBe(TranscriptionStatus::Processing);

    // Trop tôt : on laisse au rappel le temps d'arriver.
    (new PollTranscription)->handle($provider, app(StoreVerbatimTranscript::class));

    expect(Transcript::query()->count())->toBe(0);

    $this->travel(31)->seconds();

    (new PollTranscription)->handle($provider, app(StoreVerbatimTranscript::class));
    (new PollTranscription)->handle($provider, app(StoreVerbatimTranscript::class));

    expect(Transcript::query()->count())->toBe(1)
        ->and(TranscriptionJob::query()->sole()->status)->toBe(TranscriptionStatus::Done);
});

it('abandonne un travail qui traîne depuis plus d’une heure', function (): void {
    Queue::fake();
    $provider = fakeAsr()->processingFor(999);

    $recording = confirmedRecording();

    (new SubmitTranscription($recording->id))->handle(
        $provider,
        app(MediaStorage::class),
        app(StoreVerbatimTranscript::class),
    );

    $this->travel(61)->minutes();

    (new PollTranscription)->handle($provider, app(StoreVerbatimTranscript::class));

    $job = TranscriptionJob::query()->sole();

    // Mieux vaut un échec visible qu'un travail qui traîne dans la file.
    expect($job->status)->toBe(TranscriptionStatus::Failed)
        ->and($job->error)->toContain('abandonné');
});

it('marque le travail en échec et alerte le support après trois essais', function (): void {
    Queue::fake();
    fakeAsr();
    Mail::fake();

    $recording = confirmedRecording();
    TranscriptionJob::query()->create(['recording_id' => $recording->id, 'provider' => 'fake']);

    $job = new SubmitTranscription($recording->id);

    expect($job->tries)->toBe(3)
        ->and($job->backoff())->toBe([60, 300, 900]);

    $job->failed(new RuntimeException('le fournisseur ne répond plus'));

    expect(TranscriptionJob::query()->sole()->status)->toBe(TranscriptionStatus::Failed)
        ->and(OutboundMessage::query()->where('template', 'transcription_failed')->count())->toBe(1);
});

it('ne crée jamais deux verbatims courants pour une histoire', function (): void {
    Queue::fake();
    $provider = fakeAsr();

    $recording = confirmedRecording();
    $store = app(StoreVerbatimTranscript::class);

    $store->handle($recording, FakeTranscriptionProvider::resultFor('un'));
    $store->handle($recording, FakeTranscriptionProvider::resultFor('deux'));

    $current = Transcript::query()->ofKind(TranscriptKind::Verbatim)->current()->get();

    expect($current)->toHaveCount(1)
        ->and($current->first()?->version)->toBe(2)
        // L'ancien reste consultable : on n'écrase pas la parole de quelqu'un.
        ->and(Transcript::query()->count())->toBe(2);
});

it('met le récit au propre, pose le titre et annonce que le texte est prêt', function (): void {
    Event::fake([TranscriptionReady::class]);
    fakeAsr();
    app()->instance(StoryRenderer::class, new FakeStoryRenderer);

    $recording = confirmedRecording();
    $verbatim = app(StoreVerbatimTranscript::class)
        ->handle($recording, FakeTranscriptionProvider::resultFor('x'));

    (new RenderFluide((string) $verbatim?->id))->handle(app(StoryRenderer::class));

    $fluide = Transcript::query()->ofKind(TranscriptKind::Fluide)->sole();
    $story = $recording->story->refresh();

    expect($fluide->text)->not->toBe($verbatim?->text)
        ->and($fluide->text)->not->toContain('euh')
        ->and($story->title)->not->toBeNull()
        ->and(mb_strlen((string) $story->title))->toBeLessThanOrEqual(60);

    Event::assertDispatched(TranscriptionReady::class, fn (TranscriptionReady $event): bool => $event->rendered);
});

it('ne met rien au propre sans le consentement, et annonce quand même le texte', function (): void {
    Event::fake([TranscriptionReady::class]);
    fakeAsr();
    app()->instance(StoryRenderer::class, new FakeStoryRenderer);

    $story = Story::factory()->recorded()->create();
    $recording = Recording::factory()->confirmed()->create(['story_id' => $story->id]);
    fakeMediaStorage()->put((string) $recording->original_path, 'audio');

    $verbatim = app(StoreVerbatimTranscript::class)
        ->handle($recording, FakeTranscriptionProvider::resultFor('x'));

    (new RenderFluide((string) $verbatim?->id))->handle(app(StoryRenderer::class));

    // Le narrateur a droit à sa relecture dans tous les cas : c'est son récit.
    expect(Transcript::query()->ofKind(TranscriptKind::Fluide)->count())->toBe(0);

    Event::assertDispatched(TranscriptionReady::class, fn (TranscriptionReady $event): bool => ! $event->rendered);
});

it('garde le verbatim seul quand le modèle décline', function (): void {
    Event::fake([TranscriptionReady::class]);
    fakeAsr();
    app()->instance(StoryRenderer::class, (new FakeStoryRenderer)->refusing());

    $recording = confirmedRecording();
    $verbatim = app(StoreVerbatimTranscript::class)
        ->handle($recording, FakeTranscriptionProvider::resultFor('x'));

    (new RenderFluide((string) $verbatim?->id))->handle(app(StoryRenderer::class));

    // Un refus n'est pas rattrapé par un autre modèle : c'est un signal à
    // regarder, pas à contourner en silence (règle §9 du bloc).
    expect(Transcript::query()->ofKind(TranscriptKind::Fluide)->count())->toBe(0)
        ->and(Transcript::query()->count())->toBe(1);

    Event::assertDispatched(TranscriptionReady::class, fn (TranscriptionReady $event): bool => ! $event->rendered);
});

it('ne met pas deux fois le même récit au propre', function (): void {
    Event::fake([TranscriptionReady::class]);
    fakeAsr();
    app()->instance(StoryRenderer::class, new FakeStoryRenderer);

    $recording = confirmedRecording();
    $verbatim = app(StoreVerbatimTranscript::class)
        ->handle($recording, FakeTranscriptionProvider::resultFor('x'));

    (new RenderFluide((string) $verbatim?->id))->handle(app(StoryRenderer::class));
    (new RenderFluide((string) $verbatim?->id))->handle(app(StoryRenderer::class));

    expect(Transcript::query()->ofKind(TranscriptKind::Fluide)->count())->toBe(1);
});

it('range les jobs sur les bonnes files', function (): void {
    expect((new SubmitTranscription('x'))->queue)->toBe('transcription')
        ->and((new PollTranscription)->queue)->toBe('transcription')
        ->and((new RenderFluide('x'))->queue)->toBe('llm')
        ->and((new TranscodeRecording('x'))->queue)->toBe('media');
});
