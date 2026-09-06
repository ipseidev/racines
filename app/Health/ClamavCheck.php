<?php

declare(strict_types=1);

namespace App\Health;

use Spatie\Health\Checks\Check;
use Spatie\Health\Checks\Result;

/**
 * Le démon antivirus répond-il ?
 *
 * `ClamavScanner` refuse tout fichier quand le démon est muet — c'est le bon
 * choix, un fichier non scanné n'est pas un fichier propre. Mais la
 * conséquence visible est qu'**aucune photo ne passe plus**, avec un message
 * qui parle de sécurité et laisse la famille croire que ses images sont en
 * cause. Autant le savoir avant elle.
 */
final class ClamavCheck extends Check
{
    public function run(): Result
    {
        if ((string) config('services.antivirus.scanner') !== 'clamav') {
            /*
             * `ok` et non un état « ignoré » : hors production le scanner est
             * doublé, et il n'y a effectivement rien à joindre. Le résumé le
             * dit, pour qu'on ne lise pas un vert rassurant sur un
             * environnement où l'antivirus n'existe pas — et la garde qui
             * compte est ailleurs : `phpunit.xml` seul a le droit de forcer
             * le double (T-61).
             */
            return Result::make()->ok()->shortSummary('doublé');
        }

        $host = (string) config('services.antivirus.host');
        $port = (int) config('services.antivirus.port');
        $socket = @fsockopen($host, $port, $code, $message, 5);

        if ($socket === false) {
            return Result::make()->failed("Antivirus injoignable sur {$host}:{$port} — aucune photo ne peut plus être déposée.");
        }

        fwrite($socket, "zPING\0");
        $reponse = (string) fgets($socket, 16);
        fclose($socket);

        return str_contains($reponse, 'PONG')
            ? Result::make()->ok()->shortSummary('PONG')
            : Result::make()->failed('L’antivirus répond, mais pas au ping.');
    }
}
