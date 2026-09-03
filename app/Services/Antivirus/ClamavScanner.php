<?php

declare(strict_types=1);

namespace App\Services\Antivirus;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

/**
 * Le port `Scanner`, servi par le démon ClamAV.
 *
 * Écrit à la main plutôt qu'avec un paquet, et pour une fois ce n'est pas une
 * préférence : le paquet habituel (`sunspikes/clamav-validator`) ne déclare
 * pas Laravel 13, et son travail tient en quarante lignes de protocole
 * `INSTREAM`. Une dépendance de moins à suivre, et un port qu'on peut
 * doubler — ce qu'un paquet de règle de validation ne permet pas (T-118).
 *
 * `INSTREAM` plutôt que `SCAN <chemin>` : le démon tourne dans son propre
 * conteneur et ne voit pas le disque de l'application. Envoyer le flux
 * fonctionne dans les deux cas, et c'est la seule variante qui marchera aussi
 * en production si le démon migre sur une autre machine.
 *
 * **En cas de panne du démon, on refuse.** C'est le choix qui compte ici :
 * laisser passer un fichier non scanné parce que l'antivirus est tombé
 * revient à ne pas avoir d'antivirus, et cela arriverait précisément le jour
 * où il compte.
 */
final class ClamavScanner implements Scanner
{
    /** Le protocole veut des tranches ; 8 Kio est la taille usuelle. */
    private const CHUNK = 8192;

    private const TIMEOUT_SECONDS = 30;

    public function isClean(UploadedFile $file): bool
    {
        $path = $file->getRealPath();

        if ($path === false) {
            return false;
        }

        $verdict = $this->scan($path);

        if ($verdict === null) {
            // Panne du démon : on refuse, et on le dit fort. Un fichier non
            // scanné n'est pas un fichier propre.
            Log::critical('antivirus.unavailable', [
                'file_name' => $file->getClientOriginalName(),
                'host' => (string) config('services.antivirus.host'),
            ]);

            return false;
        }

        if (! $verdict) {
            Scanners::logRejection($file, 'clamav');
        }

        return $verdict;
    }

    /**
     * @return bool|null `null` si le démon n'a pas répondu.
     */
    private function scan(string $path): ?bool
    {
        $host = (string) config('services.antivirus.host');
        $port = (int) config('services.antivirus.port');

        $socket = @fsockopen($host, $port, $code, $message, self::TIMEOUT_SECONDS);

        if ($socket === false) {
            return null;
        }

        stream_set_timeout($socket, self::TIMEOUT_SECONDS);

        try {
            fwrite($socket, "zINSTREAM\0");

            $handle = fopen($path, 'rb');

            if ($handle === false) {
                return null;
            }

            while (! feof($handle)) {
                $chunk = (string) fread($handle, self::CHUNK);

                if ($chunk === '') {
                    continue;
                }

                // Chaque tranche est précédée de sa taille sur quatre octets,
                // en gros-boutien. Une tranche de taille zéro clôt le flux.
                fwrite($socket, pack('N', strlen($chunk)).$chunk);
            }

            fclose($handle);
            fwrite($socket, pack('N', 0));

            $response = (string) fgets($socket, 4096);
        } finally {
            fclose($socket);
        }

        if ($response === '') {
            return null;
        }

        // « stream: OK » ou « stream: <signature> FOUND ».
        return str_contains($response, 'OK') && ! str_contains($response, 'FOUND');
    }
}
