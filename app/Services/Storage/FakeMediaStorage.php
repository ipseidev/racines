<?php

declare(strict_types=1);

namespace App\Services\Storage;

use App\Exceptions\Domain\ObjectNotStored;
use RuntimeException;

/**
 * Stockage en mémoire pour les tests.
 *
 * Il refuse de conclure un envoi dont une part manque ou dont un ETag ne
 * correspond pas : c'est exactement le refus que doit produire R2, et c'est ce
 * refus qui garantit qu'on n'annonce jamais un enregistrement incomplet.
 */
final class FakeMediaStorage implements MediaStorage
{
    /** @var array<string, array{contents: string, mime: string|null}> */
    private array $objects = [];

    /** @var array<string, array<string, array<int, string>>> */
    private array $uploads = [];

    /** @var array<string, array<string, array{contents: string, mime: string|null}>> */
    private array $replicas = [];

    /** @var list<string> */
    private array $deleted = [];

    public function createMultipartUpload(string $key, string $mime): string
    {
        $uploadId = 'upload-'.count($this->uploads) + 1;

        $this->uploads[$key][$uploadId] = [];
        $this->objects[$key.':pending-mime'] = ['contents' => '', 'mime' => $mime];

        return $uploadId;
    }

    public function presignPart(string $key, string $uploadId, int $partNumber, int $ttlMinutes = 15): string
    {
        return "https://fake-storage.test/{$key}?uploadId={$uploadId}&partNumber={$partNumber}&expires={$ttlMinutes}";
    }

    public function putPart(string $key, string $uploadId, int $partNumber, string $contents): void
    {
        $this->uploads[$key][$uploadId][$partNumber] = $contents;
    }

    public function etagFor(string $key, string $uploadId, int $partNumber): string
    {
        return md5($this->uploads[$key][$uploadId][$partNumber] ?? '');
    }

    /**
     * @param  list<array{number: int, etag: string}>  $parts
     */
    public function completeMultipart(string $key, string $uploadId, array $parts): void
    {
        $stored = $this->uploads[$key][$uploadId] ?? throw new RuntimeException("Unknown upload [{$uploadId}].");

        $contents = '';

        foreach ($parts as $part) {
            $chunk = $stored[$part['number']] ?? throw new RuntimeException("Missing part [{$part['number']}].");

            if (md5($chunk) !== trim($part['etag'], '"')) {
                throw new RuntimeException("ETag mismatch on part [{$part['number']}].");
            }

            $contents .= $chunk;
        }

        $this->objects[$key] = [
            'contents' => $contents,
            'mime' => $this->objects[$key.':pending-mime']['mime'] ?? null,
        ];

        unset($this->uploads[$key][$uploadId], $this->objects[$key.':pending-mime']);
    }

    public function abortMultipart(string $key, string $uploadId): void
    {
        unset($this->uploads[$key][$uploadId], $this->objects[$key], $this->objects[$key.':pending-mime']);
    }

    public function head(string $key): ObjectInfo
    {
        $object = $this->objects[$key] ?? throw ObjectNotStored::at($key, 'fake');

        return new ObjectInfo(
            key: $key,
            bytes: strlen($object['contents']),
            etag: md5($object['contents']),
            mime: $object['mime'],
        );
    }

    public function copy(string $key, string $toDisk): void
    {
        $object = $this->objects[$key] ?? throw ObjectNotStored::at($key, 'fake');

        $this->replicas[$toDisk][$key] = $object;
    }

    public function temporaryUrl(string $key, int $minutes): string
    {
        return "https://fake-storage.test/{$key}?expires={$minutes}";
    }

    public function delete(string $key): void
    {
        unset($this->objects[$key]);
        $this->deleted[] = $key;
    }

    public function get(string $key): string
    {
        return ($this->objects[$key] ?? throw ObjectNotStored::at($key, 'fake'))['contents'];
    }

    public function put(string $key, string $contents, ?string $mime = null): void
    {
        $this->objects[$key] = ['contents' => $contents, 'mime' => $mime];
    }

    public function exists(string $key): bool
    {
        return array_key_exists($key, $this->objects);
    }

    public function existsOn(string $key, string $disk): bool
    {
        return array_key_exists($key, $this->replicas[$disk] ?? []);
    }

    /** @return list<string> */
    public function deletedKeys(): array
    {
        return $this->deleted;
    }
}
