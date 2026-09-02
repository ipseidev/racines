<?php

declare(strict_types=1);

namespace App\Services\Transcription;

use App\Models\Recording;
use Illuminate\Http\Request;

/**
 * Transcription simulée, pour les tests et le développement local.
 *
 * Déterministe : le texte dérive de l'identifiant de l'enregistrement, donc
 * deux exécutions donnent le même résultat. Sait aussi jouer les scénarios qui
 * comptent — rester en cours n fois, ou échouer — parce que ce sont eux qui
 * révèlent les défauts du pipeline.
 */
final class FakeTranscriptionProvider implements TranscriptionProvider
{
    private int $pendingPolls = 0;

    private bool $shouldFail = false;

    private string $mode = SubmittedJob::MODE_SYNC;

    /** @var array<string, TranscriptionRequest> */
    private array $submissions = [];

    public function name(): string
    {
        return 'fake';
    }

    public function processingFor(int $polls): self
    {
        $this->pendingPolls = $polls;
        $this->mode = SubmittedJob::MODE_POLL;

        return $this;
    }

    public function failing(): self
    {
        $this->shouldFail = true;

        return $this;
    }

    public function byWebhook(): self
    {
        $this->mode = SubmittedJob::MODE_WEBHOOK;

        return $this;
    }

    public function submit(Recording $recording, TranscriptionRequest $request): SubmittedJob
    {
        if ($this->shouldFail) {
            throw new \RuntimeException('Le fournisseur simulé refuse cette soumission.');
        }

        $this->submissions[$recording->id] = $request;

        return new SubmittedJob(
            providerJobId: 'fake-'.$recording->id,
            mode: $this->mode,
            result: $this->mode === SubmittedJob::MODE_SYNC ? self::resultFor($recording->id) : null,
        );
    }

    public function fetch(string $providerJobId): ?TranscriptionResult
    {
        if ($this->pendingPolls > 0) {
            $this->pendingPolls--;

            return null;
        }

        return self::resultFor(str_replace('fake-', '', $providerJobId));
    }

    public function parseWebhook(Request $request): ?TranscriptionResult
    {
        $id = (string) $request->input('recording_id', '');

        return $id === '' ? null : self::resultFor($id);
    }

    public function requestFor(string $recordingId): ?TranscriptionRequest
    {
        return $this->submissions[$recordingId] ?? null;
    }

    public static function resultFor(string $seed): TranscriptionResult
    {
        $sentence = 'Alors euh je me souviens de la maison de Kerhostin, ma grand-mère y faisait des crêpes.';

        return new TranscriptionResult(
            text: $sentence,
            words: array_map(
                fn (string $word, int $index): array => [
                    'word' => $word,
                    'start' => $index * 0.4,
                    'end' => $index * 0.4 + 0.35,
                    'confidence' => 0.9,
                ],
                $words = explode(' ', $sentence),
                array_keys($words),
            ),
            providerMetadata: ['seed' => $seed, 'provider' => 'fake'],
        );
    }
}
