<?php

declare(strict_types=1);

use App\Exceptions\Domain\ObjectNotStored;
use App\Models\Recording;
use App\Services\Storage\FakeMediaStorage;
use App\Services\Storage\MediaStorage;
use App\Support\ObjectKeys;

it('nomme les objets sans aucune donnée personnelle', function (): void {
    $recording = Recording::factory()->create();
    $story = $recording->story;

    $key = ObjectKeys::recordingSegment($recording, 1, 'webm');

    expect($key)->toBe(
        "projects/{$story->project_id}/stories/{$story->id}/recordings/{$recording->id}/segment-01.webm",
    );

    // Ni nom, ni courriel, ni téléphone, ni identifiant séquentiel : le chemin
    // d'un objet est aussi une donnée qui circule (doc 04 §12).
    // Le chemin ne contient que trois identifiants opaques et un nom de
    // segment : la forme elle-même interdit qu'une donnée s'y glisse.
    expect($key)->toMatch('#^projects/[0-9a-f-]{36}/stories/[0-9a-f-]{36}/recordings/[0-9a-f-]{36}/segment-\d{2}\.[a-z0-9]+$#')
        ->and($key)->not->toContain($story->narrator->display_name)
        ->and($key)->not->toContain((string) $story->narrator->phone_e164);
});

it('numérote les segments sur deux chiffres et garde l’ordre alphabétique', function (): void {
    $recording = Recording::factory()->create();

    $keys = array_map(
        fn (int $n): string => ObjectKeys::recordingSegment($recording, $n, 'webm'),
        [1, 2, 10, 11],
    );

    $sorted = $keys;
    sort($sorted);

    expect($sorted)->toBe($keys);
});

it('nomme l’objet original et le dérivé au même endroit', function (): void {
    $recording = Recording::factory()->create();

    expect(ObjectKeys::recordingOriginal($recording, 'webm'))
        ->toEndWith('/original.webm')
        ->and(ObjectKeys::recordingDerivative($recording, 'mp3'))
        ->toEndWith('/derived.mp3');
});

it('mène un envoi en plusieurs parts de bout en bout', function (): void {
    $storage = new FakeMediaStorage;
    app()->instance(MediaStorage::class, $storage);

    $key = 'projects/p/stories/s/recordings/r/segment-01.webm';
    $uploadId = $storage->createMultipartUpload($key, 'audio/webm');

    expect($uploadId)->not->toBeEmpty();

    $url = $storage->presignPart($key, $uploadId, 1);

    expect($url)->toStartWith('https://')
        ->and($url)->toContain('partNumber=1');

    $storage->putPart($key, $uploadId, 1, 'abcde');
    $storage->putPart($key, $uploadId, 2, 'fghij');

    $storage->completeMultipart($key, $uploadId, [
        ['number' => 1, 'etag' => $storage->etagFor($key, $uploadId, 1)],
        ['number' => 2, 'etag' => $storage->etagFor($key, $uploadId, 2)],
    ]);

    $info = $storage->head($key);

    expect($info->bytes)->toBe(10)
        ->and($info->mime)->toBe('audio/webm')
        ->and($info->etag)->not->toBeEmpty();
});

it('refuse de conclure un envoi dont les parts manquent', function (): void {
    $storage = new FakeMediaStorage;

    $key = 'k';
    $uploadId = $storage->createMultipartUpload($key, 'audio/webm');
    $storage->putPart($key, $uploadId, 1, 'abc');

    expect(fn () => $storage->completeMultipart($key, $uploadId, [
        ['number' => 1, 'etag' => 'mauvais-etag'],
    ]))->toThrow(RuntimeException::class);
});

it('abandonne un envoi et n’en laisse aucune trace', function (): void {
    $storage = new FakeMediaStorage;

    $key = 'k';
    $uploadId = $storage->createMultipartUpload($key, 'audio/webm');
    $storage->putPart($key, $uploadId, 1, 'abc');
    $storage->abortMultipart($key, $uploadId);

    expect($storage->exists($key))->toBeFalse();
});

it('signale l’absence d’un objet plutôt que de mentir sur sa taille', function (): void {
    $storage = new FakeMediaStorage;

    expect(fn () => $storage->head('objet/absent'))
        ->toThrow(ObjectNotStored::class);
});

it('copie vers le disque de réplique', function (): void {
    $storage = new FakeMediaStorage;

    $key = 'k';
    $uploadId = $storage->createMultipartUpload($key, 'audio/webm');
    $storage->putPart($key, $uploadId, 1, 'abc');
    $storage->completeMultipart($key, $uploadId, [
        ['number' => 1, 'etag' => $storage->etagFor($key, $uploadId, 1)],
    ]);

    $storage->copy($key, 'r2_replica');

    expect($storage->existsOn($key, 'r2_replica'))->toBeTrue()
        ->and($storage->exists($key))->toBeTrue();
});

it('produit une URL temporaire de lecture', function (): void {
    $storage = new FakeMediaStorage;
    $storage->put('k', 'contenu', 'audio/webm');

    expect($storage->temporaryUrl('k', 10))->toStartWith('https://');
});
