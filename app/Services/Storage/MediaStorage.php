<?php

declare(strict_types=1);

namespace App\Services\Storage;

use App\Exceptions\Domain\ObjectNotStored;

/**
 * Stockage des médias, derrière une interface.
 *
 * Le dossier exige une stratégie de sortie documentée par fournisseur : R2
 * aujourd'hui, Scaleway demain sans changement de code (registre des risques).
 * Aucune classe métier ne connaît S3.
 *
 * L'envoi se fait en plusieurs parts et par URL présignée : le fichier ne
 * transite jamais par le serveur applicatif, ce qui rend l'envoi reprenable
 * depuis un téléphone en 4G et évite de bloquer un worker pendant vingt
 * minutes d'audio.
 */
interface MediaStorage
{
    public function createMultipartUpload(string $key, string $mime): string;

    public function presignPart(string $key, string $uploadId, int $partNumber, int $ttlMinutes = 15): string;

    /**
     * @param  list<array{number: int, etag: string}>  $parts
     */
    public function completeMultipart(string $key, string $uploadId, array $parts): void;

    public function abortMultipart(string $key, string $uploadId): void;

    /**
     * @throws ObjectNotStored
     */
    public function head(string $key): ObjectInfo;

    public function copy(string $key, string $toDisk): void;

    public function temporaryUrl(string $key, int $minutes): string;

    public function delete(string $key): void;

    public function get(string $key): string;

    public function put(string $key, string $contents, ?string $mime = null): void;
}
