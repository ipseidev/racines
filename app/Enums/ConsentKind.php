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
}
