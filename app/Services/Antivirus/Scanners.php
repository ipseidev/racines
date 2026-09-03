<?php

declare(strict_types=1);

namespace App\Services\Antivirus;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

/**
 * Ce que les deux scanners font pareil : journaliser un refus.
 */
final class Scanners
{
    /**
     * Le refus au journal, sans le fichier.
     *
     * Ce qui part : le nom, la taille, l'empreinte. Jamais le contenu — un
     * journal n'est pas un endroit où déposer un fichier suspect, et le nom
     * suffit à répondre à la famille qui l'a envoyé.
     */
    public static function logRejection(UploadedFile $file, string $scanner): void
    {
        $path = $file->getRealPath();

        Log::warning('antivirus.rejected', [
            'file_name' => $file->getClientOriginalName(),
            'size' => $path === false ? null : filesize($path),
            'sha256' => $path === false ? null : hash_file('sha256', $path),
            'scanner' => $scanner,
        ]);
    }
}
