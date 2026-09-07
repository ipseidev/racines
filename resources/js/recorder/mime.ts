/**
 * Choix du conteneur, pour la voix comme pour la vidéo.
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

/**
 * Même logique pour l'image (T-210), et la même raison : le MP4 d'abord.
 *
 * C'est le seul conteneur que Safari produit, et le seul que tous les
 * appareils de la famille sauront lire sans qu'on ait à réencoder. Quand le
 * navigateur ne sait rendre que du WebM — Chrome Android, pour l'essentiel —
 * le serveur en tire un MP4 : la famille ne voit pas la différence, nous
 * payons quelques minutes de calcul.
 */
export const PREFERRED_VIDEO_MIME_TYPES = [
    'video/mp4',
    'video/webm;codecs=h264,opus',
    'video/webm;codecs=vp9,opus',
    'video/webm;codecs=vp8,opus',
    'video/webm',
] as const;

/** Voix seule, ou voix et visage. Le serveur le relit dans le conteneur. */
export type RecordingKind = 'audio' | 'video';

export type MimeSupportProbe = (mimeType: string) => boolean;

/**
 * Premier type produit par ce navigateur, ou `null` s'il n'en sait produire
 * aucun — cas où l'on bascule sur l'aide et la réponse écrite.
 */
/**
 * La sonde est enveloppée plutôt que passée par référence : détacher
 * `MediaRecorder.isTypeSupported` de sa classe lui fait perdre son contexte
 * sur certains moteurs.
 */
const nativeProbe: MimeSupportProbe = (mimeType) =>
    globalThis.MediaRecorder?.isTypeSupported(mimeType) ?? false;

export function candidatesFor(kind: RecordingKind): readonly string[] {
    return kind === 'video' ? PREFERRED_VIDEO_MIME_TYPES : PREFERRED_MIME_TYPES;
}

export function pickMimeType(
    kind: RecordingKind = 'audio',
    isTypeSupported: MimeSupportProbe | undefined = nativeProbe,
): string | null {
    const probe = isTypeSupported ?? nativeProbe;

    for (const candidate of candidatesFor(kind)) {
        if (probe(candidate)) {
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

/** La nature d'un brouillon se relit dans son conteneur, jamais ailleurs. */
export function kindOfMime(mimeType: string): RecordingKind {
    return baseMimeType(mimeType).startsWith('video/') ? 'video' : 'audio';
}

export function isRecordingSupported(kind: RecordingKind = 'audio'): boolean {
    return (
        typeof globalThis.MediaRecorder === 'function' &&
        typeof globalThis.navigator?.mediaDevices?.getUserMedia ===
            'function' &&
        pickMimeType(kind) !== null
    );
}
