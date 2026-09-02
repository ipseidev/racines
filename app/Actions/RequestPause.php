<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ProjectStatus;
use App\Models\Project;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Log;

/**
 * Mettre les questions en pause, jusqu'à une date.
 *
 * Trois effets, et le second est celui qu'on oublie : l'état passe à `paused`,
 * **l'envoi programmé est annulé**, et la date de reprise est posée. Une pause
 * qui laisse partir la question de mardi n'est pas une pause — et c'est
 * exactement ce que la personne redoutait en la demandant.
 *
 * Une pause a toujours un terme. Un arrêt sans date ferait disparaître le
 * projet en silence, et personne ne saurait s'il faut relancer.
 */
final class RequestPause
{
    public function handle(Project $project, CarbonInterface $until): Project
    {
        // La colonne est immuable côté modèle : on convertit ici plutôt
        // que d'élargir le type, pour que la date reste un instant figé.
        $project->paused_until = CarbonImmutable::instance($until->toDateTime());
        $project->next_prompt_at = null;
        $project->status = ProjectStatus::Paused;
        $project->save();

        Log::info('engine.pause_requested', [
            'project_id' => $project->id,
            'until' => $until->toIso8601String(),
        ]);

        return $project;
    }
}
