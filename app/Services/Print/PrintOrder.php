<?php

declare(strict_types=1);

namespace App\Services\Print;

/**
 * Une commande passée chez l'imprimeur.
 *
 * `reference` est ce que l'imprimeur nous rend pour suivre la commande. Au
 * pilote, elle est saisie **à la main** par le support une fois la commande
 * passée sur le site de l'imprimeur : il n'y a pas encore d'API, et il n'y en
 * aura pas avant le devis (T-179).
 */
final readonly class PrintOrder
{
    public function __construct(
        public string $reference,
        public ?string $ticketId = null,
    ) {}
}
