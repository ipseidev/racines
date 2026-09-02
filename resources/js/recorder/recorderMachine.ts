/**
 * Machine à états de l'enregistrement.
 *
 * Un réducteur pur : aucune API navigateur, aucun effet. C'est ce qui permet
 * de prouver au test unitaire les seules choses qui comptent vraiment pour un
 * narrateur de 80 ans — que l'explication précède toujours la demande de
 * micro, qu'une interruption ne perd rien, et qu'un envoi échoué se réessaie
 * sans repartir de zéro.
 */
export type RecorderState =
    | 'idle'
    | 'draft_found'
    | 'explaining'
    | 'requesting_permission'
    | 'permission_denied'
    | 'unsupported'
    | 'ready'
    | 'recording'
    | 'paused'
    | 'interrupted'
    | 'stopping'
    | 'reviewing'
    | 'uploading'
    | 'upload_failed'
    | 'confirmed';

export type RecorderContext = {
    elapsedSeconds: number;
    segments: number;
    warningShown: boolean;
    permissionRetries: number;
    hardStopReached: boolean;
};

export type RecorderEvent =
    | { type: 'DRAFT_FOUND' }
    | { type: 'RESUME_DRAFT' }
    | { type: 'DISCARD_DRAFT' }
    | { type: 'BEGIN' }
    | { type: 'READY' }
    | { type: 'PERMISSION_GRANTED' }
    | { type: 'PERMISSION_DENIED' }
    | { type: 'RETRY_PERMISSION' }
    | { type: 'UNSUPPORTED' }
    | { type: 'RECORD' }
    | { type: 'PAUSE' }
    | { type: 'RESUME' }
    | { type: 'TICK'; seconds: number }
    | { type: 'INTERRUPTED' }
    | { type: 'RESUME_AFTER_INTERRUPTION' }
    | { type: 'STOP' }
    | { type: 'STOPPED' }
    | { type: 'SEND' }
    | { type: 'UPLOAD_FAILED' }
    | { type: 'RETRY_UPLOAD' }
    | { type: 'CONFIRMED' }
    | { type: 'RESTART' };

export type RecorderLimits = {
    softWarningSeconds: number;
    hardStopSeconds: number;
};

export type RecorderSnapshot = {
    state: RecorderState;
    context: RecorderContext;
};

export const initialContext: RecorderContext = {
    elapsedSeconds: 0,
    segments: 0,
    warningShown: false,
    permissionRetries: 0,
    hardStopReached: false,
};

export const initialSnapshot: RecorderSnapshot = {
    state: 'idle',
    context: initialContext,
};

/** Un narrateur peut relancer la demande de micro une fois, pas dix. */
export const MAX_PERMISSION_RETRIES = 1;

export function reduce(
    snapshot: RecorderSnapshot,
    event: RecorderEvent,
    limits: RecorderLimits,
): RecorderSnapshot {
    const { state, context } = snapshot;

    switch (event.type) {
        case 'DRAFT_FOUND':
            return state === 'idle'
                ? { state: 'draft_found', context }
                : snapshot;

        case 'RESUME_DRAFT':
            // Le brouillon retrouvé est déjà un segment enregistré : on repart
            // en vérification, pas en enregistrement, pour que la personne
            // décide sans risquer d'écraser ce qu'elle a dit.
            return state === 'draft_found'
                ? {
                      state: 'reviewing',
                      context: {
                          ...context,
                          segments: Math.max(context.segments, 1),
                      },
                  }
                : snapshot;

        case 'DISCARD_DRAFT':
            return state === 'draft_found'
                ? { state: 'explaining', context: initialContext }
                : snapshot;

        case 'BEGIN':
            return state === 'idle'
                ? { state: 'explaining', context }
                : snapshot;

        case 'READY':
            // Le seul chemin vers le micro passe par l'écran d'explication :
            // on ne fait jamais surgir la demande d'autorisation sans prévenir.
            return state === 'explaining'
                ? { state: 'requesting_permission', context }
                : snapshot;

        case 'PERMISSION_GRANTED':
            return state === 'requesting_permission'
                ? { state: 'ready', context }
                : snapshot;

        case 'PERMISSION_DENIED':
            return state === 'requesting_permission'
                ? { state: 'permission_denied', context }
                : snapshot;

        case 'RETRY_PERMISSION':
            return state === 'permission_denied' &&
                context.permissionRetries < MAX_PERMISSION_RETRIES
                ? {
                      state: 'requesting_permission',
                      context: {
                          ...context,
                          permissionRetries: context.permissionRetries + 1,
                      },
                  }
                : snapshot;

        case 'UNSUPPORTED':
            return { state: 'unsupported', context };

        case 'RECORD':
            return state === 'ready'
                ? {
                      state: 'recording',
                      context: { ...context, segments: context.segments + 1 },
                  }
                : snapshot;

        case 'PAUSE':
            return state === 'recording'
                ? { state: 'paused', context }
                : snapshot;

        case 'RESUME':
            return state === 'paused'
                ? { state: 'recording', context }
                : snapshot;

        case 'TICK': {
            if (state !== 'recording') {
                return snapshot;
            }

            const elapsedSeconds = event.seconds;
            const warningShown =
                context.warningShown ||
                elapsedSeconds >= limits.softWarningSeconds;

            // Arrêt ferme à la limite dure : on ne laisse pas un narrateur
            // enregistrer une heure pour découvrir que rien n'est exploitable.
            if (elapsedSeconds >= limits.hardStopSeconds) {
                return {
                    state: 'stopping',
                    context: {
                        ...context,
                        elapsedSeconds,
                        warningShown,
                        hardStopReached: true,
                    },
                };
            }

            return {
                state: 'recording',
                context: { ...context, elapsedSeconds, warningShown },
            };
        }

        case 'INTERRUPTED':
            // Les tranches sont déjà sur le téléphone : l'interruption change
            // d'état, elle ne perd rien.
            return state === 'recording' || state === 'paused'
                ? { state: 'interrupted', context }
                : snapshot;

        case 'RESUME_AFTER_INTERRUPTION':
            return state === 'interrupted'
                ? {
                      state: 'recording',
                      context: { ...context, segments: context.segments + 1 },
                  }
                : snapshot;

        case 'STOP':
            return state === 'recording' ||
                state === 'paused' ||
                state === 'interrupted'
                ? { state: 'stopping', context }
                : snapshot;

        case 'STOPPED':
            return state === 'stopping'
                ? { state: 'reviewing', context }
                : snapshot;

        case 'SEND':
            return state === 'reviewing'
                ? { state: 'uploading', context }
                : snapshot;

        case 'UPLOAD_FAILED':
            return state === 'uploading'
                ? { state: 'upload_failed', context }
                : snapshot;

        case 'RETRY_UPLOAD':
            return state === 'upload_failed'
                ? { state: 'uploading', context }
                : snapshot;

        case 'CONFIRMED':
            // Un seul chemin vers la confirmation, et il vient du serveur.
            return state === 'uploading'
                ? { state: 'confirmed', context }
                : snapshot;

        case 'RESTART':
            return state === 'reviewing' ||
                state === 'upload_failed' ||
                state === 'confirmed'
                ? { state: 'ready', context: initialContext }
                : snapshot;

        default:
            return snapshot;
    }
}

export function canRecord(state: RecorderState): boolean {
    return state === 'ready';
}

export function isBusy(state: RecorderState): boolean {
    return (
        state === 'requesting_permission' ||
        state === 'stopping' ||
        state === 'uploading'
    );
}
