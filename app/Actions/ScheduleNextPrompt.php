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
        // promesse des 72 heures. Ensuite : le jour de la semaine choisi.
        if ($project->next_prompt_at === null) {
            $candidate = $reference->addDay()->setTime($hour, 0);

            return $candidate->greaterThan($reference) ? $candidate : $candidate->addDay();
        }

        $weeks = $project->cadence->weeks();

        $candidate = $reference
            ->addWeeks($weeks)
            ->startOfWeek(CarbonImmutable::MONDAY)
            ->addDays($project->prompt_day - 1)
            ->setTime($hour, 0);

        // Un décalage de fuseau ou un jour déjà passé dans la semaine visée
        // ne doit pas produire une échéance dans le passé.
        while ($candidate->lessThanOrEqualTo($reference)) {
            $candidate = $candidate->addWeeks($weeks);
        }

        return $candidate;
    }
}
