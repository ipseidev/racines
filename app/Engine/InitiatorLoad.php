<?php

declare(strict_types=1);

namespace App\Engine;

use App\Enums\EngineAudience;
use App\Models\EngineEvent;
use App\Models\Project;

/**
 * Combien de fois on a déjà dérangé l'Initiateur·rice ce mois-ci.
 *
 * R-7 pose quatre actions par mois, et ce n'est pas une politesse : c'est la
 * personne qui a acheté le service et qui porte le projet à bout de bras. Une
 * Initiateur·rice épuisée ne relance plus personne, et le moteur perd son
 * meilleur relais en croyant l'utiliser.
 *
 * On compte les messages **partis**, pas les intentions : un événement
 * supprimé au profit d'une règle plus prioritaire n'a fatigué personne.
 */
final class InitiatorLoad
{
    public static function requestsThisMonth(Project $project): int
    {
        return EngineEvent::query()
            ->where('project_id', $project->id)
            ->where('fired_at', '>=', now()->subDays(30))
            ->whereJsonContains('action_taken->told', EngineAudience::Initiator->value)
            ->count();
    }

    public static function isSaturated(Project $project): bool
    {
        $max = (int) config('product.engine.initiator_max_requests_per_month');

        return self::requestsThisMonth($project) >= $max;
    }
}
