<?php

declare(strict_types=1);

namespace App\Exceptions\Domain;

use DomainException;
use Throwable;

/**
 * Une transition d'état a été refusée.
 *
 * Deux cas : la paire d'états n'existe pas dans `StoryState::config()`, ou
 * elle existe mais sa garde n'est pas satisfaite. Dans les deux cas c'est un
 * refus métier, pas une erreur technique : la souveraineté du narrateur est
 * une propriété du modèle (bloc 02 §2).
 *
 * N'hérite pas de l'exception du paquet, pour ne pas être happée par la
 * traduction que `StoryState::transitionTo()` fait de celle-ci.
 */
final class ForbiddenTransition extends DomainException
{
    public static function notAllowed(string $from, string $to, ?Throwable $previous = null): self
    {
        return new self("Transition [{$from} -> {$to}] is not declared in StoryState::config().", previous: $previous);
    }

    public static function guardFailed(string $from, string $to, string $reason): self
    {
        return new self("Transition [{$from} -> {$to}] is refused: {$reason}.");
    }
}
