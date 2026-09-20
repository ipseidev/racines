<?php

declare(strict_types=1);

namespace App\Engine;

use App\Models\OutboundMessage;
use App\Models\Project;
use Carbon\CarbonImmutable;

/**
 * Combien de messages le narrateur a reçus cette semaine.
 *
 * Le moteur plafonnait à un message par jour, ce qui protège une journée et
 * pas une semaine : sept règles différentes peuvent parler sept jours de
 * suite, et la question hebdomadaire s'ajoute par-dessus sans que le plafond
 * la voie passer. Une narratrice à qui l'on avait promis « une question par
 * semaine » en a reçu quatre (T-242).
 *
 * Trois choix, et chacun répond à une façon dont un plafond peut mentir :
 *
 *  - **on compte les messages partis**, pas les déclenchements du moteur. La
 *    question hebdomadaire ne passe pas par une règle ; elle occupe pourtant
 *    la moitié du budget, et c'est la seule qui soit promise ;
 *  - **tous canaux confondus** : la personne qui reçoit un SMS puis un
 *    courriel en a reçu deux, même si le produit y voit deux adresses ;
 *  - **sur sept jours glissants**, jamais sur la semaine calendaire : un
 *    plafond qui se remet à zéro le lundi laisse passer deux messages le
 *    dimanche et deux le lundi.
 *
 * Ce que le plafond ne touche pas : la question elle-même. Elle est la
 * promesse, les relances sont le supplément — `DispatchDuePrompts` ne
 * consulte pas cette classe.
 */
final class NarratorLoad
{
    /**
     * Les empreintes des coordonnées du narrateur principal.
     *
     * La table ne garde jamais l'adresse en clair : on rejoue le hachage du
     * canal d'envoi pour retrouver ses envois sans la stocker une seconde
     * fois.
     *
     * @return list<string>
     */
    private static function recipientHashes(Project $project): array
    {
        $narrator = $project->primaryNarrator()->first();

        if ($narrator === null) {
            return [];
        }

        $hashes = [];

        foreach ([$narrator->phone_e164, $narrator->email] as $address) {
            if ($address === null || $address === '') {
                continue;
            }

            $hash = OutboundMessage::hashRecipient($address);

            if (! in_array($hash, $hashes, true)) {
                $hashes[] = $hash;
            }
        }

        return $hashes;
    }

    public static function messagesThisWeek(Project $project, CarbonImmutable $now): int
    {
        $hashes = self::recipientHashes($project);

        if ($hashes === []) {
            return 0;
        }

        return OutboundMessage::query()
            ->where('project_id', $project->id)
            ->whereIn('to_hash', $hashes)
            ->where('created_at', '>=', $now->subDays(7))
            // Un message que l'opérateur a refusé n'a fatigué personne.
            ->whereNull('failed_at')
            ->count();
    }

    public static function isSaturated(Project $project, CarbonImmutable $now): bool
    {
        $max = (int) config('product.engine.narrator_max_messages_per_week');

        return self::messagesThisWeek($project, $now) >= $max;
    }
}
