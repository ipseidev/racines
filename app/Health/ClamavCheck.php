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
 *
 * Trois états à distinguer, et c'est le résumé qui les porte : le démon
 * interrogé, le double des tests, et le contrôle débranché de D-12. Les deux
 * derniers rendent `ok` — il n'y a rien à joindre, et une sonde qui
 * avertirait en permanence enverrait le courriel horaire jusqu'à ce que
 * quelqu'un le filtre, ce qui coûterait la prochaine vraie alerte (T-216).
 * Mais ils ne disent pas la même chose, et le mot compte : « doublé » sur une
 * production débranchée aurait fait lire un environnement de test.
 */
final class ClamavCheck extends Check
{
    public function run(): Result
    {
        $scanner = (string) config('services.antivirus.scanner');

        if ($scanner === 'off') {
            /*
             * Débranché par décision, pas en panne. Le résumé nomme la
             * décision pour que la sonde renvoie à R-12 plutôt qu'à une
             * enquête.
             */
            return Result::make()->ok()->shortSummary('débranché (D-12)');
        }

        if ($scanner !== 'clamav') {
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
