<?php

declare(strict_types=1);

namespace App\Support\Product;

use Carbon\CarbonImmutable;

/**
 * Fenêtre de service d'un projet : collecte puis finalisation (R-2).
 *
 * Aucune de ces dates n'est un engagement d'hébergement : la durée
 * d'hébergement est publiée à part (R-10) et n'a jamais la même échéance.
 */
final readonly class ServiceWindow
{
    public function __construct(
        public CarbonImmutable $collectionStartsAt,
        public CarbonImmutable $collectionEndsAt,
        public CarbonImmutable $finalizationEndsAt,
    ) {}
}
