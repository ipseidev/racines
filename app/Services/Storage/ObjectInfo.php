<?php

declare(strict_types=1);

namespace App\Services\Storage;

/**
 * Ce que le stockage dit d'un objet qu'il détient réellement.
 *
 * C'est le seul témoignage qui autorise à écrire « Votre histoire est
 * enregistrée » à l'écran (doc 04 §11) : pas la fin d'un envoi, pas un code
 * HTTP 200 du navigateur, mais un objet dont le stockage confirme la taille.
 */
final readonly class ObjectInfo
{
    public function __construct(
        public string $key,
        public int $bytes,
        public string $etag,
        public ?string $mime = null,
    ) {}
}
