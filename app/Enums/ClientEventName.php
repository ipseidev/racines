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
    /*
     * Bloc 10 : quelqu'un voulait raconter **sa** propre histoire. Au pilote
     * on accompagne un proche, mais l'intérêt est une information de marché
     * qu'on aurait tort de jeter.
     */
    case SelfNarrationInterest = 'self_narration_interest';
    /*
     * T-210 : la forme choisie avant toute autorisation. C'est elle qui dira
     * si se filmer intéresse vraiment les narrateurs, ou si le bouton reste
     * un ornement — et elle se mesure au choix, pas à l'envoi, parce qu'un
     * refus de caméra est justement ce qu'on veut compter.
     */
    case CameraDenied = 'camera_denied';
    case CameraGranted = 'camera_granted';
    case VideoChosen = 'video_chosen';
    case AudioChosen = 'audio_chosen';

    /*
     * Le tour de chauffe du tout premier lien (T-247). Trois événements,
     * parce que la seule question qui vaut est « est-ce qu'il aide ? » : la
     * Gate 0A se mesure sur la réussite du premier enregistrement non
     * assisté, et on ne saura pas si cet écran l'améliore ou la retarde sans
     * pouvoir séparer celles qui l'ont joué de celles qui l'ont passé.
     */
    case FirstRunStarted = 'first_run_started';
    case FirstRunDone = 'first_run_done';
    case FirstRunSkipped = 'first_run_skipped';
}
