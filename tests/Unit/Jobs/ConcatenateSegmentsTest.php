<?php

declare(strict_types=1);

use App\Jobs\ConcatenateSegments;
use App\Models\Recording;
use App\Services\Storage\FakeMediaStorage;
use App\Services\Storage\MediaStorage;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

function storageWithSegments(Recording $recording, array $contents): FakeMediaStorage
{
    $storage = new FakeMediaStorage;
    app()->instance(MediaStorage::class, $storage);

    $segments = [];

    foreach ($contents as $index => $content) {
        $number = $index + 1;
        $key = "recordings/{$recording->id}/segment-0{$number}.webm";

        $storage->put($key, $content, 'audio/webm');
        $segments[] = ['number' => $number, 'key' => $key, 'upload_id' => "u{$number}", 'bytes' => strlen($content)];
    }

    $recording->segments = $segments;
    $recording->confirmed_at = now();
    $recording->save();

    return $storage;
}

it('recolle les segments par copie de flux, sans réencoder', function (): void {
    $recording = Recording::factory()->create();
    $storage = storageWithSegments($recording, ['premier', 'second']);

    Process::fake();

    // ffmpeg est simulé : on écrit nous-mêmes le fichier qu'il produirait.
    $directory = storage_path('app/concat/'.$recording->id);
    File::ensureDirectoryExists($directory);
    File::put($directory.'/original.webm', 'premiersecond');

    (new ConcatenateSegments($recording->id))->handle($storage);

    Process::assertRan(function (PendingProcess $process): bool {
        $command = is_array($process->command) ? $process->command : [];

        // `-c copy` : aucune perte de qualité, aucun temps de calcul.
        return in_array('-c', $command, true)
            && in_array('copy', $command, true)
            && in_array('concat', $command, true)
            && in_array('-safe', $command, true);
    });
});

it('renseigne le chemin de l’original recollé et conserve les segments', function (): void {
    $recording = Recording::factory()->create();
    $storage = storageWithSegments($recording, ['premier', 'second']);

    // ffmpeg est simulé : on écrit nous-mêmes le fichier qu'il produirait.
    Process::fake();

    $directory = storage_path('app/concat/'.$recording->id);
    File::ensureDirectoryExists($directory);
    File::put($directory.'/original.webm', 'premiersecond');

    (new ConcatenateSegments($recording->id))->handle($storage);

    $recording->refresh();

    expect($recording->original_path)->toEndWith('/original.webm')
        ->and($recording->segmentCount())->toBe(2)
        ->and($storage->exists((string) $recording->original_path))->toBeTrue();
});

it('ne fait rien pour un enregistrement d’un seul segment', function (): void {
    $recording = Recording::factory()->create();
    $storage = storageWithSegments($recording, ['seul']);

    Process::fake();

    (new ConcatenateSegments($recording->id))->handle($storage);

    Process::assertNothingRan();
    expect($recording->refresh()->original_path)->toBeNull();
});

it('échoue franchement quand ffmpeg refuse', function (): void {
    $recording = Recording::factory()->create();
    $storage = storageWithSegments($recording, ['premier', 'second']);

    Process::fake(['*' => Process::result(output: '', errorOutput: 'codec inconnu', exitCode: 1)]);

    expect(fn () => (new ConcatenateSegments($recording->id))->handle($storage))
        ->toThrow(RuntimeException::class);

    // L'original n'est pas renseigné : mieux vaut un job en échec, rejoué,
    // qu'un chemin qui pointe vers un fichier tronqué.
    expect($recording->refresh()->original_path)->toBeNull();
});

it('nettoie son répertoire de travail, même en cas d’échec', function (): void {
    $recording = Recording::factory()->create();
    $storage = storageWithSegments($recording, ['premier', 'second']);

    Process::fake(['*' => Process::result(errorOutput: 'échec', exitCode: 1)]);

    try {
        (new ConcatenateSegments($recording->id))->handle($storage);
    } catch (RuntimeException) {
        // attendu
    }

    expect(File::isDirectory(storage_path('app/concat/'.$recording->id)))->toBeFalse();
});

it('refuse un ffmpeg qui dit réussir sans rien produire', function (): void {
    $recording = Recording::factory()->create();
    $storage = storageWithSegments($recording, ['premier', 'second']);

    Process::fake();

    expect(fn () => (new ConcatenateSegments($recording->id))->handle($storage))
        ->toThrow(RuntimeException::class);

    expect($recording->refresh()->original_path)->toBeNull();
});
