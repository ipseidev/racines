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
 * Gladia, fournisseur par défaut (hébergement UE, décision T-07).
 *
 * Les noms de champs suivent l'API v2 telle que documentée à la date
 * d'écriture. La documentation officielle prime : si Gladia change ses noms,
 * on adapte **cet** adaptateur, jamais l'interface.
 *
 * Le rappel porte une signature dans son URL : sans elle, n'importe qui
 * pourrait injecter une fausse transcription dans l'histoire de quelqu'un.
 */
final readonly class GladiaProvider implements TranscriptionProvider
{
    private const BASE = 'https://api.gladia.io/v2';

    public function __construct(
        private HttpFactory $http,
        private string $apiKey,
    ) {}

    public function name(): string
    {
        return 'gladia';
    }

    public function submit(Recording $recording, TranscriptionRequest $request): SubmittedJob
    {
        $payload = [
            'audio_url' => $request->audioUrl,
            'language_config' => [
                'languages' => [$request->language],
                'code_switching' => false,
            ],
        ];

        if ($request->vocabulary !== []) {
            $payload['custom_vocabulary'] = true;
            $payload['custom_vocabulary_config'] = [
                'vocabulary' => $request->vocabulary,
            ];
        }

        if ($request->callbackUrl !== null) {
            $payload['callback'] = true;
            $payload['callback_config'] = ['url' => $request->callbackUrl];
        }

        $response = $this->client()->post(self::BASE.'/pre-recorded', $payload);

        if ($response->failed()) {
            throw new RuntimeException("Gladia a refusé la soumission : {$response->status()} {$response->body()}");
        }

        $id = $response->json('id');

        if (! is_string($id) || $id === '') {
            throw new RuntimeException('Gladia n’a pas rendu d’identifiant de travail.');
        }

        return new SubmittedJob(
            providerJobId: $id,
            mode: $request->callbackUrl === null ? SubmittedJob::MODE_POLL : SubmittedJob::MODE_WEBHOOK,
        );
    }

    public function fetch(string $providerJobId): ?TranscriptionResult
    {
        $response = $this->client()->get(self::BASE."/pre-recorded/{$providerJobId}");

        if ($response->failed()) {
            throw new RuntimeException("Gladia a refusé la lecture : {$response->status()}");
        }

        $status = (string) $response->json('status', '');

        if ($status === 'error') {
            throw new RuntimeException('Gladia rapporte une erreur de transcription.');
        }

        if ($status !== 'done') {
            return null;
        }

        /** @var array<string, mixed> $body */
        $body = $response->json();

        return self::toResult($body);
    }

    public function parseWebhook(Request $request): ?TranscriptionResult
    {
        // La signature vit dans l'URL et couvre l'identifiant d'enregistrement.
        AsrCallback::assertSignature(
            (string) $request->route('recording'),
            (string) $request->query('sig', ''),
        );

        /** @var array<string, mixed> $body */
        $body = (array) $request->json()->all();

        if ((string) data_get($body, 'status', '') !== 'done') {
            return null;
        }

        return self::toResult($body);
    }

    /**
     * @param  array<string, mixed>  $body
     */
    private static function toResult(array $body): TranscriptionResult
    {
        $text = (string) data_get($body, 'result.transcription.full_transcript', '');
        $utterances = data_get($body, 'result.transcription.utterances', []);
        $words = [];

        if (is_array($utterances)) {
            foreach ($utterances as $utterance) {
                foreach ((array) data_get($utterance, 'words', []) as $word) {
                    $words[] = [
                        'word' => (string) data_get($word, 'word', ''),
                        'start' => (float) data_get($word, 'start', 0),
                        'end' => (float) data_get($word, 'end', 0),
                        'confidence' => is_numeric(data_get($word, 'confidence')) ? (float) data_get($word, 'confidence') : null,
                    ];
                }
            }
        }

        return new TranscriptionResult(
            text: $text,
            words: $words,
            language: (string) data_get($body, 'result.transcription.languages.0', 'fr'),
            providerMetadata: [
                'provider' => 'gladia',
                'id' => data_get($body, 'id'),
                'audio_duration' => data_get($body, 'result.metadata.audio_duration'),
                'billing_time' => data_get($body, 'result.metadata.billing_time'),
            ],
        );
    }

    private function client(): PendingRequest
    {
        return $this->http
            ->withHeaders(['x-gladia-key' => $this->apiKey])
            ->acceptJson()
            ->timeout(30);
    }
}
