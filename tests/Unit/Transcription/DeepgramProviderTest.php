<?php

declare(strict_types=1);

use App\Models\Recording;
use App\Services\Transcription\DeepgramProvider;
use App\Services\Transcription\SubmittedJob;
use App\Services\Transcription\TranscriptionRequest;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Http;

function deepgram(): DeepgramProvider
{
    return new DeepgramProvider(app(HttpFactory::class), 'clé-de-test');
}

it('appelle nova-3 en français, avec la ponctuation et les termes clés', function (): void {
    Http::fake(['api.deepgram.com/*' => Http::response(providerFixture('asr/deepgram-done'))]);

    $recording = Recording::factory()->confirmed()->create();

    $job = deepgram()->submit($recording, new TranscriptionRequest(
        audioUrl: 'https://stockage.test/audio.mp3',
        vocabulary: ['Kerhostin', 'Odette'],
    ));

    // Réponse synchrone : rien à interroger ensuite.
    expect($job->mode)->toBe(SubmittedJob::MODE_SYNC)
        ->and($job->isImmediate())->toBeTrue()
        ->and($job->providerJobId)->toBe('dg-req-1');

    Http::assertSent(function ($request): bool {
        $url = (string) $request->url();

        return str_contains($url, 'model=nova-3')
            && str_contains($url, 'language=fr')
            && str_contains($url, 'smart_format=true')
            && str_contains($url, 'punctuate=true')
            && str_contains($url, 'keyterm=Kerhostin')
            && str_contains($url, 'keyterm=Odette')
            && $request->data() === ['url' => 'https://stockage.test/audio.mp3'];
    });
});

it('préfère le mot ponctué au mot brut', function (): void {
    Http::fake(['api.deepgram.com/*' => Http::response(providerFixture('asr/deepgram-done'))]);

    $job = deepgram()->submit(
        Recording::factory()->confirmed()->create(),
        new TranscriptionRequest(audioUrl: 'https://stockage.test/a.mp3'),
    );

    $result = $job->result;

    expect($result?->text)->toBe('Alors je me souviens de la maison de Kerhostin.')
        ->and($result?->words[0]['word'])->toBe('Alors')
        ->and($result?->words[1]['word'])->toBe('Kerhostin.')
        ->and($result?->providerMetadata['duration'])->toBe(132.4);
});

it('lève quand Deepgram refuse', function (): void {
    Http::fake(['api.deepgram.com/*' => Http::response(['err_msg' => 'clé invalide'], 401)]);

    expect(fn () => deepgram()->submit(
        Recording::factory()->confirmed()->create(),
        new TranscriptionRequest(audioUrl: 'https://stockage.test/a.mp3'),
    ))->toThrow(RuntimeException::class);
});

it('n’a rien à interroger, sa réponse étant immédiate', function (): void {
    expect(deepgram()->fetch('dg-req-1'))->toBeNull();
});
