<?php

declare(strict_types=1);

namespace App\Engine\Rules;

use App\Engine\BaseRule;
use App\Engine\Occurrence;
use App\Enums\EngineRuleId;
use App\Models\EngineEvent;
use App\Models\Project;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Une pause a été demandée : on le confirme, avec sa date de fin.
 *
 * C'est la seule règle qui parle **pendant** une pause, et c'est tout son
 * objet : sans elle, le narrateur ne saurait jamais que sa demande a été
 * prise en compte, et il continuerait de craindre le prochain message.
 *
 * La date de reprise est dans le message. Une pause sans terme annoncé
 * inquiète autant qu'un silence : la personne se demande si le projet est
 * mort.
 */
final class PauseRequested extends BaseRule
{
    public function id(): EngineRuleId
    {
        return EngineRuleId::PauseRequested;
    }

    public function detect(CarbonImmutable $now): Collection
    {
        return Project::query()
            ->with('primaryNarrator')
            ->whereNotNull('paused_until')
            ->where('paused_until', '>', $now)
            ->get()
            ->map(fn (Project $project): Occurrence => new Occurrence(
                project: $project,
                narrator: $project->primaryNarrator,
                // La clé porte la date de fin : une pause prolongée est une
                // nouvelle décision, et mérite sa confirmation.
                key: 'pause-'.$project->paused_until?->toDateString(),
            ))
            ->values();
    }

    public function fire(Occurrence $occurrence): array
    {
        $narrator = $occurrence->narrator;
        $until = $occurrence->project->paused_until;

        if ($narrator === null || $until === null) {
            return ['skipped' => 'no_recipient'];
        }

        return [
            ...$this->tell(
                $narrator,
                $occurrence,
                'pause_confirmed',
                ['date' => self::asFrenchDate($until)],
            ),
            'paused_until' => $until->toIso8601String(),
        ];
    }

    /**
     * La date de reprise, telle qu'une personne la lit.
     */
    private static function asFrenchDate(CarbonImmutable $until): string
    {
        // `translatedFormat` suit la locale de l'application, déjà en
        // français : pas besoin de la repasser, et le type reste franc.
        return $until->translatedFormat('j F Y');
    }

    public function resumed(EngineEvent $event, CarbonImmutable $now): ?bool
    {
        $project = $event->project;

        // La reprise, ici, c'est que la pause soit arrivée à son terme sans
        // que le projet soit abandonné entre-temps.
        if ($project->paused_until !== null && $project->paused_until->isFuture()) {
            return null;
        }

        return $project->status->acceptsNewStories();
    }
}
