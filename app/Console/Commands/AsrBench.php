<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Recording;
use App\Services\Storage\MediaStorage;
use App\Services\Transcription\DeepgramProvider;
use App\Services\Transcription\FakeTranscriptionProvider;
use App\Services\Transcription\GladiaProvider;
use App\Services\Transcription\TranscriptionProvider;
use App\Services\Transcription\TranscriptionRequest;
use App\Support\Wer;
use Illuminate\Console\Command;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Throwable;

/**
 * Banc d'essai des fournisseurs de transcription.
 *
 * Le dossier fait de la qualité ASR sur voix âgées une hypothèse à mesurer,
 * pas à supposer : cette commande la mesure, sur du vrai audio de vraies
 * personnes, avec une transcription de référence relue à la main.
 *
 * Le corpus n'est pas dans le dépôt (`tests/bench/asr/corpus/`, ignoré par
 * git) : ce sont des enregistrements de personnes identifiables.
 */
#[AsCommand(name: 'asr:bench', description: 'Mesure le WER des fournisseurs de transcription sur un corpus')]
final class AsrBench extends Command
{
    /** @var string */
    protected $signature = 'asr:bench {dir : dossier du corpus} {--providers=gladia,deepgram : fournisseurs à comparer}';

    /** @var string */
    protected $description = 'Mesure le WER des fournisseurs de transcription sur un corpus';

    /** Préfixe des objets temporaires, effacés à la fin. */
    private const PREFIX = 'bench/';

    public function handle(MediaStorage $storage): int
    {
        $directory = (string) $this->argument('dir');

        if (! File::isDirectory($directory)) {
            $this->components->error("Corpus introuvable : {$directory}");

            return self::FAILURE;
        }

        $pairs = self::pairs($directory);

        if ($pairs === []) {
            $this->components->error('Aucune paire audio + transcription de référence trouvée.');

            return self::FAILURE;
        }

        /** @var list<string> $names */
        $names = array_values(array_filter(explode(',', (string) $this->option('providers'))));
        $providers = [];

        foreach ($names as $name) {
            $providers[$name] = $this->providerFor(trim($name));
        }

        $rows = [];
        $uploaded = [];

        try {
            foreach ($pairs as $name => [$audioPath, $referencePath]) {
                $key = self::PREFIX.$name.'.'.pathinfo($audioPath, PATHINFO_EXTENSION);
                $storage->put($key, File::get($audioPath));
                $uploaded[] = $key;

                $reference = File::get($referencePath);

                foreach ($providers as $providerName => $provider) {
                    $rows[] = $this->measure($provider, $providerName, $name, $key, $reference, $storage);
                }
            }
        } finally {
            foreach ($uploaded as $key) {
                $storage->delete($key);
            }
        }

        $path = $this->writeReport($rows, array_keys($providers), count($pairs));

        $this->components->info("Compte rendu écrit dans {$path}");
        $this->table(
            ['fichier', 'fournisseur', 'WER', 'durée (s)'],
            array_map(
                fn (array $row): array => [
                    $row['file'],
                    $row['provider'],
                    $row['wer'] === null ? 'échec' : number_format($row['wer'] * 100, 1).' %',
                    $row['seconds'] === null ? '—' : number_format($row['seconds'], 1),
                ],
                $rows,
            ),
        );

        return self::SUCCESS;
    }

    /**
     * @return array{file: string, provider: string, wer: float|null, seconds: float|null, error: string|null}
     */
    private function measure(
        TranscriptionProvider $provider,
        string $providerName,
        string $file,
        string $key,
        string $reference,
        MediaStorage $storage,
    ): array {
        $recording = new Recording;
        $recording->id = 'bench-'.$file;

        $startedAt = microtime(true);

        try {
            $submitted = $provider->submit($recording, new TranscriptionRequest(
                audioUrl: $storage->temporaryUrl($key, 60),
            ));

            $result = $submitted->result;

            // Un fournisseur asynchrone : on interroge jusqu'à ce qu'il rende.
            for ($attempt = 0; $result === null && $attempt < 60; $attempt++) {
                sleep(5);
                $result = $provider->fetch((string) $submitted->providerJobId);
            }

            if ($result === null) {
                throw new RuntimeException('aucun résultat après cinq minutes');
            }

            return [
                'file' => $file,
                'provider' => $providerName,
                'wer' => Wer::compute($reference, $result->text),
                'seconds' => microtime(true) - $startedAt,
                'error' => null,
            ];
        } catch (Throwable $exception) {
            return [
                'file' => $file,
                'provider' => $providerName,
                'wer' => null,
                'seconds' => null,
                'error' => $exception->getMessage(),
            ];
        }
    }

    /**
     * @param  list<array{file: string, provider: string, wer: float|null, seconds: float|null, error: string|null}>  $rows
     * @param  list<string>  $providers
     */
    private function writeReport(array $rows, array $providers, int $files): string
    {
        $lines = [
            '# Banc d’essai ASR — '.now()->toDateString(),
            '',
            "Corpus : {$files} enregistrement(s). Fournisseurs : ".implode(', ', $providers).'.',
            '',
            '**Règle de choix** : fournisseur par défaut = WER médian le plus bas ; si l’écart',
            'est inférieur ou égal à 2 points, Gladia l’emporte (hébergement UE, décision T-07).',
            '',
            '## Par fichier',
            '',
            '| fichier | fournisseur | WER | durée de traitement | remarque |',
            '|---|---|---|---|---|',
        ];

        foreach ($rows as $row) {
            $wer = $row['wer'] === null ? '—' : number_format($row['wer'] * 100, 1).' %';
            $seconds = $row['seconds'] === null ? '—' : number_format($row['seconds'], 1).' s';

            $lines[] = "| {$row['file']} | {$row['provider']} | {$wer} | {$seconds} | ".($row['error'] ?? '').' |';
        }

        $lines[] = '';
        $lines[] = '## Synthèse';
        $lines[] = '';
        $lines[] = '| fournisseur | WER médian | WER p90 | durée moyenne | échecs |';
        $lines[] = '|---|---|---|---|---|';

        foreach ($providers as $provider) {
            $forProvider = array_values(array_filter($rows, fn (array $row): bool => $row['provider'] === $provider));

            /** @var list<float> $wers */
            $wers = array_values(array_map(
                fn (array $row): float => (float) $row['wer'],
                array_filter($forProvider, fn (array $row): bool => $row['wer'] !== null),
            ));

            /** @var list<float> $durations */
            $durations = array_values(array_map(
                fn (array $row): float => (float) $row['seconds'],
                array_filter($forProvider, fn (array $row): bool => $row['seconds'] !== null),
            ));

            $median = Wer::median($wers);
            $p90 = Wer::percentile($wers, 90);
            $failures = count($forProvider) - count($wers);

            $lines[] = sprintf(
                '| %s | %s | %s | %s | %d |',
                $provider,
                $median === null ? '—' : number_format($median * 100, 1).' %',
                $p90 === null ? '—' : number_format($p90 * 100, 1).' %',
                $durations === [] ? '—' : number_format(array_sum($durations) / count($durations), 1).' s',
                $failures,
            );
        }

        $lines[] = '';
        $lines[] = '_Coût : à reporter à la main depuis la console de chaque fournisseur ; les prix';
        $lines[] = 'ne sont pas exposés par leurs API._';

        $path = base_path('docs/spikes/asr-'.now()->toDateString().'.md');
        File::ensureDirectoryExists(dirname($path));
        File::put($path, implode("\n", $lines)."\n");

        return $path;
    }

    /**
     * Paires `x.(wav|mp3|m4a)` + `x.txt`.
     *
     * @return array<string, array{string, string}>
     */
    private static function pairs(string $directory): array
    {
        $pairs = [];

        foreach (File::files($directory) as $file) {
            if (! in_array(mb_strtolower($file->getExtension()), ['wav', 'mp3', 'm4a', 'webm', 'ogg'], true)) {
                continue;
            }

            $reference = $directory.'/'.$file->getFilenameWithoutExtension().'.txt';

            if (File::exists($reference)) {
                $pairs[$file->getFilenameWithoutExtension()] = [$file->getPathname(), $reference];
            }
        }

        ksort($pairs);

        return $pairs;
    }

    private function providerFor(string $name): TranscriptionProvider
    {
        $http = app(HttpFactory::class);

        return match ($name) {
            'fake' => new FakeTranscriptionProvider,
            'gladia' => new GladiaProvider($http, (string) config('services.asr.gladia_key')),
            'deepgram' => new DeepgramProvider($http, (string) config('services.asr.deepgram_key')),
            default => throw new RuntimeException("Fournisseur inconnu : {$name}"),
        };
    }
}
