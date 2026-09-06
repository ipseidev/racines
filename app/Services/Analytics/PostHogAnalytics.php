<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Analytics\PiiGuard;
use App\Enums\AnalyticsEvent;
use App\Jobs\SendAnalyticsEvent;

/**
 * Les mesures, envoyées à PostHog (cloud UE).
 *
 * Trois décisions, et aucune n'est un détail.
 *
 * **La garde anti-fuite passe avant l'envoi**, pas après. Une propriété
 * refusée fait échouer l'appel, ici, dans le processus qui l'a produite — pas
 * dans un worker où personne ne lit la sortie.
 *
 * **L'identifiant est un haché salé par la clé de l'application.** PostHog a
 * besoin de relier les événements d'un même projet pour dessiner un
 * entonnoir ; il n'a besoin de rien d'autre. `sha256(project_id + APP_KEY)`
 * lui donne exactement cela : une clé stable qui ne remonte à personne, et
 * qu'un vidage de la base de PostHog ne permet pas d'inverser.
 *
 * **L'envoi part en file.** Un appel HTTP vers un tiers dans le chemin d'une
 * requête, c'est la latence de ce tiers ajoutée à la nôtre — et sa panne
 * devenant la nôtre. Une mesure n'a jamais le droit de ralentir un
 * enregistrement.
 */
final class PostHogAnalytics implements Analytics
{
    /**
     * @param  array<string, mixed>  $properties
     */
    public function capture(
        AnalyticsEvent $event,
        array $properties = [],
        ?string $distinctId = null,
    ): void {
        PiiGuard::assertClean($properties);

        SendAnalyticsEvent::dispatch(
            $event->value,
            $properties,
            self::pseudonym($distinctId),
        );
    }

    /**
     * L'identifiant pseudonymisé.
     *
     * `null` devient un identifiant fixe et non un aléatoire : les
     * événements sans sujet — une visite, une règle du moteur globale — se
     * regroupent alors sous une même entrée au lieu de fabriquer un
     * utilisateur par événement, ce qui ferait mentir tous les comptes.
     */
    public static function pseudonym(?string $subject): string
    {
        if ($subject === null || $subject === '') {
            return 'systeme';
        }

        return hash('sha256', $subject.'|'.config('app.key'));
    }
}
