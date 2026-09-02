/**
 * Choix du conteneur audio.
 *
 * L'ordre n'est pas arbitraire. `audio/mp4` d'abord parce que c'est le seul
 * que Safari iOS sait produire, et iOS est la moitié du parc de nos
 * narrateurs. Ensuite Opus, meilleur rapport qualité/poids pour de la parole
 * envoyée en 4G. `audio/webm` nu en dernier recours.
 */
export const PREFERRED_MIME_TYPES = [
    'audio/mp4',
    'audio/webm;codecs=opus',
    'audio/webm',
    'audio/ogg;codecs=opus',
] as const;

export type MimeSupportProbe = (mimeType: string) => boolean;

/**
 * Premier type produit par ce navigateur, ou `null` s'il n'en sait produire
 * aucun — cas où l'on bascule sur l'aide et la réponse écrite.
 */
export function pickMimeType(
    isTypeSupported: MimeSupportProbe | undefined = globalThis.MediaRecorder
        ?.isTypeSupported,
): string | null {
    if (typeof isTypeSupported !== 'function') {
        return null;
    }

    for (const candidate of PREFERRED_MIME_TYPES) {
        if (isTypeSupported(candidate)) {
            return candidate;
        }
    }

    return null;
}

/**
 * Type déclaré au serveur : sans les paramètres de codec, que la liste
 * `product.recording.accepted_mimes` ne connaît pas.
 */
export function baseMimeType(mimeType: string): string {
    return (mimeType.split(';')[0] ?? mimeType).trim().toLowerCase();
}

export function isRecordingSupported(): boolean {
    return (
        typeof globalThis.MediaRecorder === 'function' &&
        typeof globalThis.navigator?.mediaDevices?.getUserMedia ===
            'function' &&
        pickMimeType() !== null
    );
}
