<?php

declare(strict_types=1);

use App\Models\Recording;
use App\Services\Transcription\GladiaProvider;
use App\Services\Transcription\SubmittedJob;
use App\Services\Transcription\TranscriptionRequest;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Http;

function gladia(): GladiaProvider
{
    return new GladiaProvider(app(HttpFactory::class), 'clé-de-test');
}

it('soumet l’audio avec la langue, le vocabulaire et l’URL de rappel', function (): void {
    Http::fake(['api.gladia.io/*' => Http::response(['id' => 'gladia-job-1'], 201)]);

    $recording = Recording::factory()->confirmed()->create();

    $job = gladia()->submit($recording, new TranscriptionRequest(
        audioUrl: 'https://stockage.test/audio.mp3?signature',
        vocabulary: ['Kerhostin', 'Odette'],
        callbackUrl: 'https://liens.test/webhooks/asr/gladia/'.$recording->id.'?sig=abc',
    ));

    expect($job->providerJobId)->toBe('gladia-job-1')
        ->and($job->mode)->toBe(SubmittedJob::MODE_WEBHOOK);

    Http::assertSent(function ($request): bool {
        $body = $request->data();

        return str_ends_with((string) $request->url(), '/v2/pre-recorded')
            && $body['audio_url'] === 'https://stockage.test/audio.mp3?signature'
            && $body['language_config']['languages'] === ['fr']
            // Le vocabulaire donné d'avance vaut mieux qu'une correction après.
            && $body['custom_vocabulary_config']['vocabulary'] === ['Kerhostin', 'Odette']
            && str_contains((string) $body['callback_config']['url'], '/webhooks/asr/gladia/')
            && $request->hasHeader('x-gladia-key', 'clé-de-test');
    });
});

it('demande une interrogation quand aucun rappel n’est possible', function (): void {
    Http::fake(['api.gladia.io/*' => Http::response(['id' => 'gladia-job-1'], 201)]);

    $job = gladia()->submit(
        Recording::factory()->confirmed()->create(),
        new TranscriptionRequest(audioUrl: 'https://stockage.test/a.mp3'),
    );

    expect($job->mode)->toBe(SubmittedJob::MODE_POLL);
});

it('n’envoie pas de vocabulaire vide', function (): void {
    Http::fake(['api.gladia.io/*' => Http::response(['id' => 'x'], 201)]);

    gladia()->submit(
        Recording::factory()->confirmed()->create(),
        new TranscriptionRequest(audioUrl: 'https://stockage.test/a.mp3'),
    );

    Http::assertSent(fn ($request): bool => ! array_key_exists('custom_vocabulary', $request->data()));
});

it('lève quand Gladia refuse la soumission', function (): void {
    Http::fake(['api.gladia.io/*' => Http::response(['message' => 'clé invalide'], 401)]);

    expect(fn () => gladia()->submit(
        Recording::factory()->confirmed()->create(),
        new TranscriptionRequest(audioUrl: 'https://stockage.test/a.mp3'),
    ))->toThrow(RuntimeException::class);
});

it('traduit un résultat terminé en texte et mots horodatés', function (): void {
    Http::fake(['api.gladia.io/*' => Http::response(providerFixture('asr/gladia-done'))]);

    $result = gladia()->fetch('gladia-job-1');

    expect($result?->text)->toBe('Alors euh je me souviens de la maison de Kerhostin.')
        ->and($result?->language)->toBe('fr')
        ->and($result?->words)->toHaveCount(3)
        ->and($result?->words[0])->toBe(['word' => 'Alors', 'start' => 0.1, 'end' => 0.5, 'confidence' => 0.98])
        ->and($result?->providerMetadata['audio_duration'])->toBe(132.4);
});

it('rend null tant que le travail n’est pas terminé', function (): void {
    Http::fake(['api.gladia.io/*' => Http::response(providerFixture('asr/gladia-processing'))]);

    expect(gladia()->fetch('gladia-job-1'))->toBeNull();
});

it('lève quand Gladia rapporte une erreur de transcription', function (): void {
    Http::fake(['api.gladia.io/*' => Http::response(providerFixture('asr/gladia-error'))]);

    expect(fn () => gladia()->fetch('gladia-job-1'))->toThrow(RuntimeException::class);
});

// La lecture des rappels est éprouvée sur la vraie route, dans
// `tests/Feature/Webhooks/AsrWebhookTest.php` : construire une requête liée à
// une route à la main éprouverait le framework, pas notre code.
