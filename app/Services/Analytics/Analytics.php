<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Enums\AnalyticsEvent;

/**
 * Là où partent les mesures.
 *
 * L'interface existe dès le bloc 08 alors que PostHog n'arrive qu'au bloc 15 :
 * les événements de la chaîne H2 doivent être émis **pendant** qu'on écrit le
 * code qui les produit, sinon ils sont ajoutés après coup, aux mauvais
 * endroits, et la mesure ne veut plus rien dire.
 */
interface Analytics
{
    /**
     * @param  array<string, mixed>  $properties  Jamais de donnée
     *                                            personnelle : des
     *                                            identifiants opaques et des
     *                                            durées.
     */
    public function capture(
        AnalyticsEvent $event,
        array $properties = [],
        ?string $distinctId = null,
    ): void;
}
