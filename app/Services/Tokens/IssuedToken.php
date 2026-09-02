<?php

declare(strict_types=1);

namespace App\Services\Tokens;

use App\Models\AccessToken;

/**
 * Le jeton en clair et sa ligne en base, le temps de l'envoyer.
 *
 * `$plain` n'existe que dans cet objet, entre l'émission et l'envoi du
 * message. Il ne revient jamais de la base : personne, pas même le support,
 * ne peut relire un lien déjà envoyé — on en émet un nouveau.
 */
final readonly class IssuedToken
{
    public function __construct(
        public string $plain,
        public AccessToken $token,
    ) {}
}
