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
    /**
     * Les identifiants internes qui deviennent des hachés avant de partir.
     *
     * Sans cette table, le hachage du `distinct_id` ne servirait à rien :
     * un `project_id` en clair dans les propriétés permettrait de rejoindre
     * les deux jeux de données et de désanonymiser tout le reste. Le port
     * promet « des identifiants opaques » — c'est ici que la promesse est
     * tenue, une fois, plutôt qu'à trente points d'appel.
     */
    private const HASHED = [
        'project_id' => 'project_hash',
        'story_id' => 'story_hash',
        'family_member_id' => 'family_member_hash',
        'narrator_id' => 'narrator_hash',
        'user_id' => 'user_hash',
        'order_id' => 'order_hash',
        'book_id' => 'book_hash',
        'export_id' => 'export_hash',
    ];

    public function capture(
        AnalyticsEvent $event,
        array $properties = [],
        ?string $distinctId = null,
    ): void {
        PiiGuard::assertClean($properties);

        SendAnalyticsEvent::dispatch(
            $event->value,
            self::opaque($properties),
            self::pseudonym($distinctId),
        );
    }

    /**
     * @param  array<string, mixed>  $properties
     * @return array<string, mixed>
     */
    private static function opaque(array $properties): array
    {
        $sortie = [];

        foreach ($properties as $key => $value) {
            $renomme = self::HASHED[$key] ?? null;

            if ($renomme !== null && is_string($value)) {
                $sortie[$renomme] = self::pseudonym($value);

                continue;
            }

            $sortie[$key] = $value;
        }

        return $sortie;
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
