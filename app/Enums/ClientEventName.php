<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasTranslatedLabel;

/**
 * Événements que la page d'enregistrement rapporte au serveur.
 *
 * Liste fermée : c'est elle qui permet de mesurer le taux d'échec de capture
 * avant confirmation, que le doc 04 §11 fixe sous 2 %. Le bloc 15 les reprend
 * dans les tableaux de bord.
 */
enum ClientEventName: string
{
    use HasTranslatedLabel;

    case MicDenied = 'mic_denied';
    case MicGranted = 'mic_granted';
    case RecorderUnsupported = 'recorder_unsupported';
    case RecordingStarted = 'recording_started';
    case RecordingPaused = 'recording_paused';
    case RecordingResumed = 'recording_resumed';
    case RecordingStopped = 'recording_stopped';
    case PageHidden = 'page_hidden';
    case Interrupted = 'interrupted';
    case ResumedFromDraft = 'resumed_from_draft';
    case DraftDiscarded = 'draft_discarded';
    case SoftWarningReached = 'soft_warning_reached';
    case HardStopReached = 'hard_stop_reached';
    case UploadStarted = 'upload_started';
    case UploadRetried = 'upload_retried';
    case UploadFailed = 'upload_failed';
    case StorageQuotaLow = 'storage_quota_low';
    case WrittenAnswerChosen = 'written_answer_chosen';
}
