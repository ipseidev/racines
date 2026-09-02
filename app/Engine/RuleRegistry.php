<?php

declare(strict_types=1);

namespace App\Engine;

use App\Engine\Rules\DecliningCadence;
use App\Engine\Rules\InvitationNotAccepted;
use App\Engine\Rules\LinkNotOpened;
use App\Engine\Rules\MicDenied;
use App\Engine\Rules\NarratorSilence10d;
use App\Engine\Rules\NarratorSilence21d;
use App\Engine\Rules\PauseRequested;
use App\Engine\Rules\RecordedNotValidated;
use App\Engine\Rules\RecordingAbandoned;
use App\Engine\Rules\ThreeStoriesNoReaction;
use App\Engine\Rules\ValidatedNotListened;
use App\Enums\EngineRuleId;

/**
 * Les onze règles, **dans l'ordre de l'annexe C**.
 *
 * L'ordre est du comportement, pas de la mise en forme : quand deux règles
 * veulent parler au même narrateur le même jour, celle qui vient en premier
 * gagne. Réordonner cette liste change ce que les familles reçoivent.
 */
final class RuleRegistry
{
    /** @var list<class-string<Rule>> */
    private const RULES = [
        InvitationNotAccepted::class,
        LinkNotOpened::class,
        MicDenied::class,
        RecordingAbandoned::class,
        RecordedNotValidated::class,
        ValidatedNotListened::class,
        ThreeStoriesNoReaction::class,
        NarratorSilence10d::class,
        NarratorSilence21d::class,
        PauseRequested::class,
        DecliningCadence::class,
    ];

    /**
     * @return list<Rule>
     */
    public function all(): array
    {
        return array_map(fn (string $rule): Rule => app($rule), self::RULES);
    }

    /**
     * La règle qui porte cet identifiant, pour mesurer une reprise.
     */
    public function find(EngineRuleId $id): ?Rule
    {
        foreach ($this->all() as $rule) {
            if ($rule->id() === $id) {
                return $rule;
            }
        }

        return null;
    }
}
