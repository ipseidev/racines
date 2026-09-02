<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\EngineRuleId;
use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Notifications\EngineNotification;
use Illuminate\Support\Facades\Log;

/**
 * Reprendre à l'échéance d'une pause.
 *
 * Le message de reprise ne demande rien : « voici une question, sans
 * obligation, elle vous attendra le temps qu'il faudra ». Quelqu'un qui sort
 * d'une pause a souvent traversé quelque chose, et une relance enjouée au
 * premier matin serait déplacée.
 *
 * Les projets résiliés, achevés ou gelés par un deuil ne se réveillent pas :
 * une pause n'est pas ce qui les a arrêtés.
 */
final readonly class ResumeFromPause
{
    public function __construct(private ScheduleNextPrompt $schedule) {}

    /**
     * @return int Le nombre de projets réveillés.
     */
    public function handle(): int
    {
        $due = Project::query()
            ->with('primaryNarrator')
            ->whereNotNull('paused_until')
            ->where('paused_until', '<=', now())
            ->whereIn('status', [ProjectStatus::Paused->value, ProjectStatus::Active->value])
            ->get();

        $resumed = 0;

        foreach ($due as $project) {
            $project->paused_until = null;
            $project->status = ProjectStatus::Active;
            $project->next_prompt_at = $this->schedule->handle($project);
            $project->save();

            $narrator = $project->primaryNarrator;

            if ($narrator !== null) {
                $narrator->notify(new EngineNotification(
                    rule: EngineRuleId::PauseRequested,
                    key: 'resume',
                    project: $project,
                    payload: ['occurrence_key' => 'resume:'.$project->id],
                ));
            }

            $resumed++;

            Log::info('engine.pause_resumed', [
                'project_id' => $project->id,
                'next_prompt_at' => $project->next_prompt_at?->toIso8601String(),
            ]);
        }

        return $resumed;
    }
}
