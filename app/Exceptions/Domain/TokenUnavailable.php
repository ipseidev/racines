<?php

declare(strict_types=1);

namespace App\Exceptions\Domain;

use App\Enums\TokenType;
use DomainException;

/**
 * Un lien ne peut pas servir.
 *
 * Une seule famille d'exceptions pour tous les motifs, parce que l'appelant
 * n'a qu'une chose à faire : montrer la page amicale correspondante. Le motif
 * (`reason`) devient le texte affiché ; le type dit si la page est celle du
 * narrateur ou celle des proches.
 *
 * Aucune de ces exceptions ne dit jamais pourquoi techniquement : la page
 * narrateur parle de lien expiré, pas de jeton introuvable en base.
 */
abstract class TokenUnavailable extends DomainException
{
    public function __construct(
        private readonly string $reason,
        private readonly ?TokenType $tokenType = null,
        string $message = '',
    ) {
        parent::__construct($message !== '' ? $message : "Token unavailable: {$reason}.");
    }

    public function reason(): string
    {
        return $this->reason;
    }

    public function tokenType(): ?TokenType
    {
        return $this->tokenType;
    }

    /**
     * Un lien d'enregistrement expiré ou révoqué se redemande ; un lien
     * d'écoute se redemande à la personne qui l'a envoyé, pas au produit.
     */
    public function canRequestNewLink(): bool
    {
        return $this->tokenType === TokenType::Record
            && in_array($this->reason, ['expired', 'revoked'], true);
    }
}
