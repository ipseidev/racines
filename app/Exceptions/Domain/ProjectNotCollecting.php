<?php

declare(strict_types=1);

namespace App\Exceptions\Domain;

use DomainException;

/**
 * Le projet ne peut pas recevoir de nouvelle question : il est en pause, ou
 * gelé à la demande de la famille après un décès (R-4, doc 04 §3).
 */
final class ProjectNotCollecting extends DomainException
{
    public static function status(string $status): self
    {
        return new self("A project in status [{$status}] does not accept new stories.");
    }
}
