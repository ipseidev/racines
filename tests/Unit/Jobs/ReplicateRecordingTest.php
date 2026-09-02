<?php

declare(strict_types=1);

use App\Jobs\ReplicateRecording;
use App\Models\Recording;
use App\Services\Storage\FakeMediaStorage;
use App\Services\Storage\MediaStorage;

function fakeStorage(): FakeMediaStorage
{
    $storage = new FakeMediaStorage;
    app()->instance(MediaStorage::class, $storage);

    return $storage;
}

it('copie l’audio confirmé vers le disque de réplique', function (): void {
    $storage = fakeStorage();
    $recording = Recording::factory()->confirmed()->create();

    $storage->put((string) $recording->original_path, 'audio', 'audio/webm');

    (new ReplicateRecording($recording->id))->handle($storage);

    $recording->refresh();

    expect($storage->existsOn((string) $recording->original_path, 'r2_replica'))->toBeTrue()
        ->and($recording->replicated_at)->not->toBeNull()
        ->and($recording->replica_path)->toBe($recording->original_path);
});

it('réplique tous les segments d’un enregistrement interrompu', function (): void {
    $storage = fakeStorage();
    $recording = Recording::factory()->create();

    $recording->segments = [
        ['number' => 1, 'key' => 'a/segment-01.webm', 'upload_id' => 'u1', 'bytes' => 10],
        ['number' => 2, 'key' => 'a/segment-02.webm', 'upload_id' => 'u2', 'bytes' => 12],
    ];
    $recording->confirmed_at = now();
    $recording->save();

    $storage->put('a/segment-01.webm', 'un');
    $storage->put('a/segment-02.webm', 'deux');

    (new ReplicateRecording($recording->id))->handle($storage);

    expect($storage->existsOn('a/segment-01.webm', 'r2_replica'))->toBeTrue()
        ->and($storage->existsOn('a/segment-02.webm', 'r2_replica'))->toBeTrue()
        ->and($recording->refresh()->replicated_at)->not->toBeNull();
});

it('ne réplique rien tant que l’enregistrement n’est pas confirmé', function (): void {
    $storage = fakeStorage();
    $recording = Recording::factory()->create();

    (new ReplicateRecording($recording->id))->handle($storage);

    expect($recording->refresh()->replicated_at)->toBeNull();
});

it('est idempotent : rejoué, il ne recopie pas', function (): void {
    $storage = fakeStorage();
    $recording = Recording::factory()->confirmed()->create();
    $storage->put((string) $recording->original_path, 'audio');

    $job = new ReplicateRecording($recording->id);
    $job->handle($storage);

    $firstRun = $recording->refresh()->replicated_at;

    $this->travel(1)->hour();
    $job->handle($storage);

    expect($recording->refresh()->replicated_at?->getTimestamp())->toBe($firstRun?->getTimestamp());
});

it('attend de plus en plus longtemps entre deux essais, jusqu’à une heure', function (): void {
    $job = new ReplicateRecording('inexistant');

    expect($job->tries)->toBe(5)
        ->and($job->backoff())->toBe([30, 120, 600, 1800, 3600]);
});

it('ne casse pas sur un enregistrement disparu', function (): void {
    $storage = fakeStorage();

    (new ReplicateRecording('01a00000-0000-7000-8000-000000000000'))->handle($storage);

    expect(true)->toBeTrue();
});
