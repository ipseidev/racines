<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Les onze règles du moteur de complétion (annexe C).
 *
 * L'**ordre de déclaration est l'ordre de priorité** : quand deux règles
 * pourraient parler au même narrateur le même jour, celle qui vient en
 * premier ici gagne et l'autre est consignée comme supprimée (règle §9 du
 * bloc 09). Réordonner cette énumération change donc le comportement du
 * produit, pas seulement une liste.
 */
enum EngineRuleId: string
{
    case InvitationNotAccepted = 'invitation_not_accepted';
    case LinkNotOpened = 'link_not_opened';
    case MicDenied = 'mic_denied';
    case RecordingAbandoned = 'recording_abandoned';
    case RecordedNotValidated = 'recorded_not_validated';
    case ValidatedNotListened = 'validated_not_listened';
    case ThreeStoriesNoReaction = 'three_stories_no_reaction';
    case NarratorSilence10d = 'narrator_silence_10d';
    case NarratorSilence21d = 'narrator_silence_21d';
    case PauseRequested = 'pause_requested';
    case DecliningCadence = 'declining_cadence';

    /**
     * Rang de priorité, 0 étant le plus prioritaire.
     */
    public function priority(): int
    {
        return array_search($this, self::cases(), true) ?: 0;
    }
}
