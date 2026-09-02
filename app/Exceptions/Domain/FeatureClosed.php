<?php

declare(strict_types=1);

namespace App\Exceptions\Domain;

use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Une fonctionnalité derrière un drapeau fermé.
 *
 * Rendue en **404**, pas en 403 : une fonctionnalité qu'on n'a pas ouverte ne
 * doit pas s'annoncer. Un « interdit » dirait qu'elle existe et qu'elle est
 * réservée, ce qui invite à insister ; un « introuvable » ne dit rien.
 */
final class FeatureClosed extends HttpException
{
    public static function make(string $feature): self
    {
        return new self(404, "Feature [{$feature}] is not open for this project.");
    }
}
