<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Prépare le stockage objet local : les trois seaux et leur politique CORS.
 *
 * MinIO émule R2 en développement (T-24). Sans cette préparation, l'envoi
 * présigné échoue de deux façons difficiles à diagnostiquer : le seau n'existe
 * pas, ou le navigateur refuse le `PUT` faute de CORS et sans exposer l'ETag
 * — or c'est l'ETag qui prouve qu'une part est arrivée.
 *
 * Refuse de tourner en production : là, les seaux sont créés à la main avec
 * une juridiction UE et un DPA (doc 04 §5).
 */
#[AsCommand(name: 'storage:prepare-local', description: 'Crée les seaux locaux et leur politique CORS')]
final class PrepareLocalStorage extends Command
{
    /** @var string */
    protected $signature = 'storage:prepare-local';

    /** @var string */
    protected $description = 'Crée les seaux locaux et leur politique CORS';

    public function handle(): int
    {
        if (app()->isProduction()) {
            $this->components->error('En production, les seaux sont créés à la main (juridiction UE, DPA).');

            return self::FAILURE;
        }

        $client = $this->client();
        $cors = $this->corsRules();

        foreach (['r2', 'r2_replica', 'r2_backups'] as $disk) {
            $bucket = (string) config("filesystems.disks.{$disk}.bucket");

            if ($bucket === '') {
                $this->components->warn("Disque [{$disk}] sans seau configuré : ignoré.");

                continue;
            }

            try {
                $client->headBucket(['Bucket' => $bucket]);
                $this->components->twoColumnDetail($bucket, 'déjà présent');
            } catch (S3Exception) {
                $client->createBucket(['Bucket' => $bucket]);
                $this->components->twoColumnDetail($bucket, 'créé');
            }

            if ($cors === null) {
                continue;
            }

            try {
                $client->putBucketCors(['Bucket' => $bucket, 'CORSConfiguration' => $cors]);
                $this->components->twoColumnDetail($bucket, 'CORS appliqué');
            } catch (S3Exception $exception) {
                // MinIO n'implémente pas `PutBucketCors` : il autorise toutes
                // les origines par défaut, ce qui convient en local. Sur R2,
                // la règle de `docker/minio/cors.json` est à reporter dans la
                // console Cloudflare (bloc 16).
                $this->components->twoColumnDetail(
                    $bucket,
                    str_contains($exception->getMessage(), 'NotImplemented')
                        ? 'CORS non configurable ici (permissif par défaut)'
                        : 'CORS refusé : '.$exception->getAwsErrorCode(),
                );
            }
        }

        return self::SUCCESS;
    }

    /**
     * @return array{CORSRules: list<array<string, mixed>>}|null
     */
    private function corsRules(): ?array
    {
        $path = base_path('docker/minio/cors.json');

        if (! is_file($path)) {
            return null;
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        if (! is_array($decoded) || ! is_array($decoded['CORSRules'] ?? null)) {
            return null;
        }

        /** @var array{CORSRules: list<array<string, mixed>>} $decoded */
        return $decoded;
    }

    private function client(): S3Client
    {
        $config = config('filesystems.disks.r2');

        return new S3Client([
            'version' => 'latest',
            'region' => is_array($config) ? (string) ($config['region'] ?? 'auto') : 'auto',
            'endpoint' => is_array($config) ? (string) ($config['endpoint'] ?? '') : '',
            'use_path_style_endpoint' => true,
            'credentials' => [
                'key' => is_array($config) ? (string) ($config['key'] ?? '') : '',
                'secret' => is_array($config) ? (string) ($config['secret'] ?? '') : '',
            ],
        ]);
    }
}
