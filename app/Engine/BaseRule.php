<?php

declare(strict_types=1);

namespace App\Engine;

use App\Enums\Channel;
use App\Enums\EngineAudience;
use App\Models\EngineEvent;
use App\Models\FamilyMember;
use App\Models\Narrator;
use App\Models\Project;
use App\Models\User;
use App\Notifications\EngineNotification;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

/**
 * Ce que les onze règles partagent.
 *
 * Trois choses seulement : compter ses propres déclenchements, envoyer un
 * message du moteur, et déclarer qu'elle ne sait pas encore si la relance a
 * porté. Tout le reste — la détection, la limite, le texte — est propre à
 * chaque règle, et c'est bien : une base qui en ferait davantage
 * uniformiserait des règles que le dossier a écrites différentes.
 */
abstract class BaseRule implements Rule
{
    public function audience(Occurrence $occurrence): EngineAudience
    {
        return EngineAudience::Narrator;
    }

    public function isCapped(Occurrence $occurrence): bool
    {
        return false;
    }

    public function resumed(EngineEvent $event, CarbonImmutable $now): ?bool
    {
        return null;
    }

    /**
     * Combien de fois cette règle a **réellement parlé** pour ce projet.
     *
     * Les événements supprimés au profit d'une règle plus prioritaire ne
     * comptent pas : un message qui n'est pas parti n'a relancé personne, et
     * le compter consommerait un rappel que le narrateur n'a jamais reçu.
     */
    protected function firedFor(Project $project, ?CarbonImmutable $since = null): int
    {
        return self::sentEvents()
            ->where('project_id', $project->id)
            ->where('rule_id', $this->id()->value)
            ->when($since !== null, fn ($query) => $query->where('fired_at', '>=', $since))
            ->count();
    }

    /**
     * Combien de fois cette règle a parlé au sujet de **cette histoire**.
     */
    protected function firedForStory(?string $storyId): int
    {
        if ($storyId === null) {
            return 0;
        }

        return self::sentEvents()
            ->where('rule_id', $this->id()->value)
            ->where('story_id', $storyId)
            ->count();
    }

    /**
     * Les événements dont le message est effectivement parti.
     *
     * @return Builder<EngineEvent>
     */
    protected static function sentEvents(): Builder
    {
        return EngineEvent::query()->whereRaw("action_taken ->> 'told' is not null");
    }

    /**
     * Le dernier déclenchement de cette règle pour ce projet.
     */
    protected function lastFiredFor(Project $project): ?EngineEvent
    {
        return self::sentEvents()
            ->where('project_id', $project->id)
            ->where('rule_id', $this->id()->value)
            ->orderByDesc('fired_at')
            ->first();
    }

    /**
     * Envoie le message de la règle et rend de quoi remplir `action_taken`.
     *
     * @param  array<string, string>  $replacements
     * @param  list<array{label: string, url: string}>  $actions
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function tell(
        Narrator|User|FamilyMember $recipient,
        Occurrence $occurrence,
        string $key,
        array $replacements = [],
        array $actions = [],
        array $payload = [],
        ?Channel $forceChannel = null,
    ): array {
        $notification = new EngineNotification(
            rule: $this->id(),
            key: $key,
            project: $occurrence->project,
            replacements: $replacements,
            actions: $actions,
            payload: ['occurrence_key' => $occurrence->occurrenceKey(), ...$payload],
            forceChannel: $forceChannel,
        );

        $recipient->notify($notification);

        return [
            'message' => 'engine.'.$key,
            'attempt' => $occurrence->attempt,
            'actions' => array_column($actions, 'label'),
            ...$payload,
        ];
    }
}
