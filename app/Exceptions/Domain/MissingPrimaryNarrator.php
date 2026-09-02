<?php

declare(strict_types=1);

namespace App\Exceptions\Domain;

use DomainException;

/**
 * Aucun narrateur principal sur ce projet : il n'y a personne à qui poser la
 * question.
 */
final class MissingPrimaryNarrator extends DomainException
{
    public static function forProject(string $projectId): self
    {
        return new self("Project [{$projectId}] has no primary narrator.");
    }
}
