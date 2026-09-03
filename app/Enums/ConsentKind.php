<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasTranslatedLabel;

/**
 * Consentements distincts et révocables (doc 04 §2, glossaire §5).
 */
enum ConsentKind: string
{
    use HasTranslatedLabel;

    case VoiceRecording = 'voice_recording';
    case Transcription = 'transcription';
    case AiRendering = 'ai_rendering';
    case FamilySharing = 'family_sharing';
    case SensitiveCategories = 'sensitive_categories';
    case PhoneCallRecording = 'phone_call_recording';
    case PhotoRights = 'photo_rights';
    case PostMortemDirectives = 'post_mortem_directives';
    // Déléguer sa validation à un proche (bloc 07 §6.7). Exception au
    // principe de souveraineté, et donc consentement à part entière.
    case MandateDelegation = 'mandate_delegation';
    /*
     * Les deux consentements de l'acheteur (bloc 10 §6.3). Séparés, et
     * séparés de l'acceptation des CGV : un démarrage immédiat fait perdre
     * une partie du droit de rétractation, et une case à cocher qui mêle les
     * deux ne vaut pas consentement.
     */
    case EarlyServiceStart = 'early_service_start';
    case MarketingEmail = 'marketing_email';
}
