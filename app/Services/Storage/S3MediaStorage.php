<?php

declare(strict_types=1);

namespace App\Services\Storage;

use App\Exceptions\Domain\ObjectNotStored;
use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use Illuminate\Support\Facades\Storage;

/**
 * Stockage sur R2, en envoi multipart présigné.
 *
 * Le client S3 est construit depuis la configuration du disque, et non depuis
 * des variables lues à la main : changer d'hébergeur reste un changement de
 * `config/filesystems.php`.
 */
final class S3MediaStorage implements MediaStorage
{
    public function __construct(
        private readonly string $disk = 'r2',
    ) {}

    public function createMultipartUpload(string $key, string $mime): string
    {
        $result = $this->client()->createMultipartUpload([
            'Bucket' => $this->bucket(),
            'Key' => $key,
            'ContentType' => $mime,
        ]);

        return (string) $result['UploadId'];
    }

    public function presignPart(string $key, string $uploadId, int $partNumber, int $ttlMinutes = 15): string
    {
        $client = $this->client();

        $command = $client->getCommand('UploadPart', [
            'Bucket' => $this->bucket(),
            'Key' => $key,
            'UploadId' => $uploadId,
            'PartNumber' => $partNumber,
        ]);

        return (string) $client->createPresignedRequest($command, "+{$ttlMinutes} minutes")->getUri();
    }

    /**
     * @param  list<array{number: int, etag: string}>  $parts
     */
    public function completeMultipart(string $key, string $uploadId, array $parts): void
    {
        usort($parts, fn (array $a, array $b): int => $a['number'] <=> $b['number']);

        $this->client()->completeMultipartUpload([
            'Bucket' => $this->bucket(),
            'Key' => $key,
            'UploadId' => $uploadId,
            'MultipartUpload' => [
                'Parts' => array_map(
                    fn (array $part): array => ['PartNumber' => $part['number'], 'ETag' => $part['etag']],
                    $parts,
                ),
            ],
        ]);
    }

    public function abortMultipart(string $key, string $uploadId): void
    {
        $this->client()->abortMultipartUpload([
            'Bucket' => $this->bucket(),
            'Key' => $key,
            'UploadId' => $uploadId,
        ]);
    }

    public function head(string $key): ObjectInfo
    {
        try {
            $result = $this->client()->headObject([
                'Bucket' => $this->bucket(),
                'Key' => $key,
            ]);
        } catch (S3Exception) {
            throw ObjectNotStored::at($key, $this->disk);
        }

        return new ObjectInfo(
            key: $key,
            bytes: (int) $result['ContentLength'],
            etag: trim((string) $result['ETag'], '"'),
            mime: is_string($result['ContentType'] ?? null) ? $result['ContentType'] : null,
        );
    }

    public function copy(string $key, string $toDisk): void
    {
        $target = (string) config("filesystems.disks.{$toDisk}.bucket");

        $this->client()->copyObject([
            'Bucket' => $target,
            'Key' => $key,
            'CopySource' => $this->bucket().'/'.$key,
        ]);
    }

    public function temporaryUrl(string $key, int $minutes): string
    {
        return Storage::disk($this->disk)->temporaryUrl($key, now()->addMinutes($minutes));
    }

    public function delete(string $key): void
    {
        Storage::disk($this->disk)->delete($key);
    }

    public function get(string $key): string
    {
        $contents = Storage::disk($this->disk)->get($key);

        return $contents ?? throw ObjectNotStored::at($key, $this->disk);
    }

    public function put(string $key, string $contents, ?string $mime = null): void
    {
        Storage::disk($this->disk)->put($key, $contents, $mime === null ? [] : ['ContentType' => $mime]);
    }

    private function client(): S3Client
    {
        $config = config("filesystems.disks.{$this->disk}");

        return new S3Client([
            'version' => 'latest',
            'region' => is_array($config) ? (string) ($config['region'] ?? 'auto') : 'auto',
            'endpoint' => is_array($config) ? (string) ($config['endpoint'] ?? '') : '',
            'use_path_style_endpoint' => is_array($config) && (bool) ($config['use_path_style_endpoint'] ?? false),
            'credentials' => [
                'key' => is_array($config) ? (string) ($config['key'] ?? '') : '',
                'secret' => is_array($config) ? (string) ($config['secret'] ?? '') : '',
            ],
        ]);
    }

    private function bucket(): string
    {
        return (string) config("filesystems.disks.{$this->disk}.bucket");
    }
}
