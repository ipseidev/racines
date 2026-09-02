<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Enums\AnalyticsEvent;
use Illuminate\Support\Facades\Log;

/**
 * Les mesures dans le journal, en attendant PostHog (bloc 15).
 *
 * Ce n'est pas un bouchon : c'est ce qui permet de vérifier dès maintenant
 * qu'un événement part au bon moment, avec les bonnes propriétés et sans
 * donnée personnelle. Le canal dédié `analytics` les garde à part des
 * journaux d'application.
 */
final class LogAnalytics implements Analytics
{
    /** @var list<array{event: string, properties: array<string, mixed>, distinct_id: string|null}> */
    private array $captured = [];

    /**
     * @param  array<string, mixed>  $properties
     */
    public function capture(
        AnalyticsEvent $event,
        array $properties = [],
        ?string $distinctId = null,
    ): void {
        $this->captured[] = [
            'event' => $event->value,
            'properties' => $properties,
            'distinct_id' => $distinctId,
        ];

        Log::info('analytics.'.$event->value, [
            ...$properties,
            'distinct_id' => $distinctId,
        ]);
    }

    /**
     * Ce qui a été mesuré, pour les tests.
     *
     * @return list<array{event: string, properties: array<string, mixed>, distinct_id: string|null}>
     */
    public function captured(?AnalyticsEvent $event = null): array
    {
        if ($event === null) {
            return $this->captured;
        }

        return array_values(array_filter(
            $this->captured,
            fn (array $one): bool => $one['event'] === $event->value,
        ));
    }
}
