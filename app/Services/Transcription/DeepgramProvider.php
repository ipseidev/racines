<?php

declare(strict_types=1);

namespace App\Services\Transcription;

use App\Models\Recording;
use App\Support\AsrCallback;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Deepgram, second adaptateur (décision T-07).
 *
 * Sa réponse est synchrone : pas de rappel, pas d'interrogation. Le pipeline
 * l'accepte tel quel — c'est pour cela que `SubmittedJob` porte un mode.
 *
 * Il sert deux fois : de recours si Gladia tombe, et de point de comparaison
 * dans le banc d'essai. Le dossier veut une stratégie de sortie documentée par
 * fournisseur ; en avoir deux qui marchent est la seule preuve qui compte.
 */
final readonly class DeepgramProvider implements TranscriptionProvider
{
    private const BASE = 'https://api.deepgram.com/v1/listen';

    public function __construct(
        private HttpFactory $http,
        private string $apiKey,
        private string $model = 'nova-3',
    ) {}

    public function name(): string
    {
        return 'deepgram';
    }

    public function submit(Recording $recording, TranscriptionRequest $request): SubmittedJob
    {
        $query = [
            'model' => $this->model,
            'language' => $request->language,
            'smart_format' => 'true',
            'punctuate' => 'true',
            'paragraphs' => 'true',
        ];

        $url = self::BASE.'?'.http_build_query($query);

        foreach ($request->vocabulary as $term) {
            // `keyterm` se répète, ce que `http_build_query` ne sait pas faire.
            $url .= '&keyterm='.rawurlencode($term);
        }

        $response = $this->client()->post($url, ['url' => $request->audioUrl]);

        if ($response->failed()) {
            throw new RuntimeException("Deepgram a refusé la soumission : {$response->status()} {$response->body()}");
        }

        /** @var array<string, mixed> $body */
        $body = $response->json();

        return new SubmittedJob(
            providerJobId: (string) data_get($body, 'metadata.request_id', ''),
            mode: SubmittedJob::MODE_SYNC,
            result: self::toResult($body),
        );
    }

    public function fetch(string $providerJobId): ?TranscriptionResult
    {
        // Deepgram répond tout de suite : il n'y a rien à interroger.
        return null;
    }

    public function parseWebhook(Request $request): ?TranscriptionResult
    {
        AsrCallback::assertSignature(
            (string) $request->route('recording'),
            (string) $request->query('sig', ''),
        );

        /** @var array<string, mixed> $body */
        $body = (array) $request->json()->all();

        return data_get($body, 'results') === null ? null : self::toResult($body);
    }

    /**
     * @param  array<string, mixed>  $body
     */
    private static function toResult(array $body): TranscriptionResult
    {
        $alternative = data_get($body, 'results.channels.0.alternatives.0', []);
        $words = [];

        foreach ((array) data_get($alternative, 'words', []) as $word) {
            $words[] = [
                'word' => (string) (data_get($word, 'punctuated_word') ?? data_get($word, 'word', '')),
                'start' => (float) data_get($word, 'start', 0),
                'end' => (float) data_get($word, 'end', 0),
                'confidence' => is_numeric(data_get($word, 'confidence')) ? (float) data_get($word, 'confidence') : null,
            ];
        }

        return new TranscriptionResult(
            text: (string) data_get($alternative, 'transcript', ''),
            words: $words,
            language: (string) data_get($body, 'results.channels.0.detected_language', 'fr'),
            providerMetadata: [
                'provider' => 'deepgram',
                'request_id' => data_get($body, 'metadata.request_id'),
                'duration' => data_get($body, 'metadata.duration'),
                'model' => data_get($body, 'metadata.model_info'),
            ],
        );
    }

    private function client(): PendingRequest
    {
        return $this->http
            ->withToken($this->apiKey, 'Token')
            ->acceptJson()
            ->timeout(120);
    }
}
