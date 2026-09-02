<?php

declare(strict_types=1);

use App\Enums\TranscriptionStatus;
use App\Models\Recording;
use App\Models\Story;
use App\Models\Transcript;
use App\Models\TranscriptionJob;
use App\Services\Transcription\FakeTranscriptionProvider;
use App\Services\Transcription\GladiaProvider;
use App\Services\Transcription\TranscriptionProvider;
use App\Support\AsrCallback;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Queue;

function recordingReady(): Recording
{
    $story = Story::factory()->recorded()->create();

    return Recording::factory()->confirmed()->create(['story_id' => $story->id]);
}

it('enregistre le verbatim quand le rappel est correctement signé', function (): void {
    Queue::fake();
    app()->instance(TranscriptionProvider::class, new GladiaProvider(app(HttpFactory::class), 'clé'));

    $recording = recordingReady();
    TranscriptionJob::query()->create([
        'recording_id' => $recording->id,
        'provider' => 'gladia',
        'provider_job_id' => 'gladia-job-1',
        'status' => TranscriptionStatus::Processing,
        'submitted_at' => now(),
    ]);

    $url = AsrCallback::urlFor('gladia', $recording->id);

    $this->postJson($url, providerFixture('asr/gladia-done'))->assertOk();

    expect(Transcript::query()->sole()->text)->toContain('Kerhostin')
        ->and(TranscriptionJob::query()->sole()->status)->toBe(TranscriptionStatus::Done);
});

it('refuse un rappel dont la signature ne tient pas', function (): void {
    Queue::fake();
    app()->instance(TranscriptionProvider::class, new GladiaProvider(app(HttpFactory::class), 'clé'));

    $recording = recordingReady();

    // Sans cette vérification, n'importe qui pourrait injecter une fausse
    // transcription dans l'histoire de quelqu'un — et le texte est ce qui va
    // dans le livre.
    $this->postJson(
        "/webhooks/asr/gladia/{$recording->id}?sig=inventée",
        providerFixture('asr/gladia-done'),
    )->assertForbidden();

    expect(Transcript::query()->count())->toBe(0);
});

it('refuse un rappel sans signature', function (): void {
    app()->instance(TranscriptionProvider::class, new GladiaProvider(app(HttpFactory::class), 'clé'));

    $recording = recordingReady();

    $this->postJson("/webhooks/asr/gladia/{$recording->id}")->assertForbidden();
});

it('accepte sans broncher un enregistrement inconnu', function (): void {
    app()->instance(TranscriptionProvider::class, new FakeTranscriptionProvider);

    $this->postJson(
        AsrCallback::urlFor('fake', '01a00000-0000-7000-8000-000000000000'),
        ['recording_id' => 'x'],
    )->assertStatus(202);
});

it('accepte sans broncher un corps qui n’est pas encore un résultat', function (): void {
    Queue::fake();
    app()->instance(TranscriptionProvider::class, new GladiaProvider(app(HttpFactory::class), 'clé'));

    $recording = recordingReady();

    $this->postJson(
        AsrCallback::urlFor('gladia', $recording->id),
        providerFixture('asr/gladia-processing'),
    )->assertStatus(202);

    expect(Transcript::query()->count())->toBe(0);
});

it('refuse un fournisseur qui n’est pas des nôtres', function (): void {
    $recording = recordingReady();

    $this->postJson("/webhooks/asr/inconnu/{$recording->id}?sig=x")->assertNotFound();
});
