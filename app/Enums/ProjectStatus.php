<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasTranslatedLabel;

/**
 * Cycle de vie d'un projet (annexe B, R-2).
 *
 * `frozen_bereavement` est l'état de gel demandé par la famille après un
 * décès : le projet cesse toute sollicitation sans rien supprimer.
 */
enum ProjectStatus: string
{
    use HasTranslatedLabel;

    case Draft = 'draft';
    case AwaitingAcceptance = 'awaiting_acceptance';
    case Active = 'active';
    case Paused = 'paused';
    case Dormant = 'dormant';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case FrozenBereavement = 'frozen_bereavement';

    /**
     * Une question ne s'ajoute pas à un projet en pause ni gelé par un deuil.
     * Les autres états n'ont pas de restriction à ce stade : le calendrier de
     * collecte, lui, est borné par `Project::serviceWindow()`.
     */
    public function acceptsNewStories(): bool
    {
        return ! in_array($this, [self::Paused, self::FrozenBereavement], true);
    }
}
