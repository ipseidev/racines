<?php

declare(strict_types=1);

namespace App\Logging;

use Illuminate\Log\Logger;
use Monolog\Logger as MonologLogger;

/**
 * Branche le masquage des jetons sur un canal de journalisation.
 *
 * Déclaré en `tap` sur chaque canal de `config/logging.php` : un canal ajouté
 * sans ce `tap` fait échouer `RedactTokensProcessorTest`, pour qu'on ne
 * puisse pas ouvrir une fuite en ajoutant un canal.
 */
final class RedactTokensTap
{
    public function __invoke(Logger $logger): void
    {
        $monolog = $logger->getLogger();

        // Un canal dont le journal n'est pas un Monolog — cas d'un pilote
        // exotique — ne prend pas de processeur. On préfère ne rien faire
        // plutôt qu'échouer au démarrage.
        if ($monolog instanceof MonologLogger) {
            $monolog->pushProcessor(new RedactTokens);
        }
    }
}
