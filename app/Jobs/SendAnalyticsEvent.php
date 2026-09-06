<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use PostHog\PostHog;
use Throwable;

/**
 * Envoyer une mesure, hors du chemin de la requête.
 *
 * **Une mesure ratée n'est jamais une erreur.** Si PostHog ne répond pas, on
 * consigne et on passe : perdre un point de mesure est ennuyeux, faire
 * échouer un enregistrement de narrateur parce qu'un outil d'analytique est
 * en panne serait absurde. D'où l'absence de nouvelle tentative — un
 * événement rejoué trois heures plus tard fausserait la chronologie sans rien
 * ajouter.
 */
final class SendAnalyticsEvent implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 15;

    /**
     * @param  array<string, mixed>  $properties
     */
    public function __construct(
        private readonly string $event,
        private readonly array $properties,
        private readonly string $distinctId,
    ) {
        $this->onQueue('notifications');
    }

    public function handle(): void
    {
        $key = (string) config('services.posthog.key');

        if ($key === '') {
            return;
        }

        try {
            PostHog::init($key, ['host' => (string) config('services.posthog.host')]);

            PostHog::capture([
                'distinctId' => $this->distinctId,
                'event' => $this->event,
                'properties' => $this->properties,
            ]);
        } catch (Throwable $exception) {
            Log::info('analytics.send_failed', [
                'event' => $this->event,
                'reason' => $exception->getMessage(),
            ]);
        }
    }
}
