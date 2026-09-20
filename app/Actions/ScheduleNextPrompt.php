<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ProjectStatus;
use App\Models\Project;
use Carbon\CarbonImmutable;

/**
 * Décide quand partira la prochaine question.
 *
 * Trois exigences se croisent ici (décision T-28, R-9) :
 *
 *  - le créneau et le jour sont ceux que le narrateur a choisis, dans **son**
 *    fuseau : recevoir sa question à 3 h du matin est le meilleur moyen de ne
 *    jamais y répondre ;
 *  - la première question part le lendemain, pas dans une semaine — le dossier
 *    veut le premier enregistrement sous 72 heures ;
 *  - une pause demandée est respectée, et rien ne part après la fin de la
 *    période de collecte.
 *
 * Depuis que la cadence peut valoir deux ou trois questions par semaine, le
 * jour choisi n'est plus l'unique jour d'envoi mais le **premier** : les
 * autres s'en déduisent par `Cadence::dayOffsets()`, et c'est
 * `Cadence::minimumGapDays()` — non le numéro de semaine — qui garantit
 * qu'aucune question n'en talonne une autre.
 *
 * Le calcul passe par le fuseau du projet puis revient en UTC, ce qui fait que
 * le changement d'heure d'octobre ne décale pas les envois d'une heure.
 */
final class ScheduleNextPrompt
{
    public function handle(Project $project, ?CarbonImmutable $from = null): ?CarbonImmutable
    {
        if (! $project->status->acceptsNewStories() || $project->status === ProjectStatus::Completed) {
            return null;
        }

        $timezone = $project->timezone;
        $reference = ($from ?? now())->setTimezone($timezone);

        // Une pause demandée déplace le point de départ, elle ne l'annule pas.
        if ($project->paused_until !== null && $project->paused_until->isFuture()) {
            $reference = $project->paused_until->setTimezone($timezone);
        }

        $next = $this->firstSlotAfter($project, $reference);

        if ($project->collection_ends_at !== null && $next->greaterThan($project->collection_ends_at)) {
            return null;
        }

        return $next->utc();
    }

    /**
     * Applique la planification à un projet et retourne l'échéance retenue.
     */
    public function apply(Project $project, ?CarbonImmutable $from = null): ?CarbonImmutable
    {
        $next = $this->handle($project, $from);

        $project->next_prompt_at = $next;
        $project->save();

        return $next;
    }

    private function firstSlotAfter(Project $project, CarbonImmutable $reference): CarbonImmutable
    {
        $hour = $project->prompt_slot->hour();

        // Première question : le lendemain au créneau choisi, pour tenir la
        // promesse des 72 heures. Ensuite : les jours de la cadence.
        if ($project->next_prompt_at === null) {
            $candidate = $reference->addDay()->setTime($hour, 0);

            return $candidate->greaterThan($reference) ? $candidate : $candidate->addDay();
        }

        $cadence = $project->cadence;

        /*
         * L'écart se mesure **en jours**, jamais en heures.
         *
         * La référence est l'instant du dernier envoi, et le créneau du jour
         * visé tombe souvent quelques minutes avant lui : une question partie
         * à 9 h 05 un mercredi comparée à un créneau de 9 h 00 le mercredi
         * suivant est « en avance » de trois cents secondes, et l'échéance
         * sautait une semaine entière pour ça. Le narrateur a choisi un jour,
         * pas une seconde : on compare des dates.
         */
        $notBefore = $reference->addDays($cadence->minimumGapDays())->startOfDay();

        $chosen = $reference
            ->startOfWeek(CarbonImmutable::MONDAY)
            ->addDays($project->prompt_day - 1);

        $next = null;

        // Les jours de la cadence, semaine après semaine, dans l'ordre : le
        // premier qui respecte l'écart minimal est le bon. Les décalages
        // restent sous une semaine, donc l'énumération est chronologique.
        for ($week = 0; $week <= 5 && $next === null; $week++) {
            foreach ($cadence->dayOffsets() as $offset) {
                $slot = $chosen->addWeeks($week)->addDays($offset)->setTime($hour, 0);

                if ($slot->startOfDay()->greaterThanOrEqualTo($notBefore)) {
                    $next = $slot;

                    break;
                }
            }
        }

        // Cinq semaines couvrent le plus grand des écarts — quinze jours —
        // avec de la marge. La branche ne se prend pas ; elle existe pour
        // qu'une cadence future mal réglée rende une date plutôt que rien.
        return $next ?? $chosen->addWeeks($cadence->weeks())->setTime($hour, 0);
    }
}
