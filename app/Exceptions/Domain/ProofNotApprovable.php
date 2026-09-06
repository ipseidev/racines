<?php

declare(strict_types=1);

namespace App\Exceptions\Domain;

use RuntimeException;

/**
 * Ce bon à tirer ne peut pas être approuvé.
 *
 * Deux cas. Les cases ne sont pas cochées — et elles ne se pré-cochent
 * jamais, l'accord à l'impression définitive est un acte explicite comme la
 * validation d'une histoire (R-4, doc 04 §10). Ou le livre n'est pas dans un
 * état où l'approbation a un sens : pas de PDF, ou une commande déjà passée.
 */
final class ProofNotApprovable extends RuntimeException
{
    public static function missingAcknowledgement(): self
    {
        return new self('L’accord demande les deux confirmations : l’imprimé est définitif, et les noms propres ont été relus.');
    }

    public static function notReady(): self
    {
        return new self('Ce bon à tirer n’est pas prêt à être approuvé.');
    }
}
