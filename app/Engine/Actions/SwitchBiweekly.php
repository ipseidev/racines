<?php

declare(strict_types=1);

namespace App\Engine\Actions;

use App\Actions\ScheduleNextPrompt;
use App\Enums\Cadence;
use App\Models\AccessToken;
use App\Models\Project;
use Illuminate\Support\Facades\Log;

/**
 * Passer à une question toutes les deux semaines.
 *
 * « Réduire vaut mieux qu'arrêter » : c'est la phrase que cette action met en
 * œuvre. Un narrateur qui trouve le rythme trop soutenu n'a pas d'autre issue
 * que d'arrêter, à moins qu'on ne lui propose de ralentir — et personne ne
 * pense à demander.
 */
final readonly class SwitchBiweekly implements OneTapAction
{
    public function __construct(private ScheduleNextPrompt $schedule) {}

    public static function name(): string
    {
        return 'switch_biweekly';
    }

    /** @return array<string, mixed> */
    public function preview(AccessToken $token): array
    {
        return [
            'title' => __('initiator.one_tap.switch_biweekly.title'),
            'body' => __('initiator.one_tap.switch_biweekly.body'),
            'button' => __('initiator.one_tap.switch_biweekly.button'),
        ];
    }

    /** @return array<string, mixed> */
    public function execute(AccessToken $token): array
    {
        $project = $token->subject;

        if (! $project instanceof Project) {
            return ['done' => false];
        }

        $project->cadence = Cadence::Biweekly;
        $project->save();

        // Le prochain envoi est recalculé **et posé** tout de suite : sans
        // ça, la question suivante partirait à l'ancien rythme et le geste
        // paraîtrait sans effet.
        $next = $this->schedule->handle($project->refresh());

        $project->next_prompt_at = $next;
        $project->save();

        Log::info('engine.cadence_switched', [
            'project_id' => $project->id,
            'cadence' => Cadence::Biweekly->value,
            'next_prompt_at' => $next?->toIso8601String(),
        ]);

        return [
            'done' => true,
            'message' => __('initiator.one_tap.switch_biweekly.done'),
            'next_prompt_at' => $next?->toIso8601String(),
        ];
    }
}
