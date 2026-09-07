import { describe, expect, it } from 'vitest';

import {
    baseMimeType,
    isRecordingSupported,
    kindOfMime,
    pickMimeType,
    PREFERRED_MIME_TYPES,
    PREFERRED_VIDEO_MIME_TYPES,
} from './mime';

const supporting =
    (...supported: string[]) =>
    (mimeType: string) =>
        supported.includes(mimeType);

describe('choix du conteneur audio', () => {
    it('préfère audio/mp4, le seul que Safari iOS sait produire', () => {
        expect(pickMimeType('audio', supporting(...PREFERRED_MIME_TYPES))).toBe(
            'audio/mp4',
        );
    });

    it('retombe sur Opus quand mp4 est absent', () => {
        expect(
            pickMimeType(
                'audio',
                supporting('audio/webm;codecs=opus', 'audio/webm'),
            ),
        ).toBe('audio/webm;codecs=opus');
    });

    it('accepte webm nu en dernier recours', () => {
        expect(pickMimeType('audio', supporting('audio/webm'))).toBe(
            'audio/webm',
        );
    });

    it('accepte ogg quand c’est tout ce qu’il y a', () => {
        expect(pickMimeType('audio', supporting('audio/ogg;codecs=opus'))).toBe(
            'audio/ogg;codecs=opus',
        );
    });

    it('rend null quand le navigateur ne sait rien produire', () => {
        expect(pickMimeType('audio', supporting())).toBeNull();
        expect(pickMimeType('audio', undefined)).toBeNull();
    });

    it('déclare au serveur un type sans paramètre de codec', () => {
        expect(baseMimeType('audio/webm;codecs=opus')).toBe('audio/webm');
        expect(baseMimeType('audio/mp4')).toBe('audio/mp4');
        expect(baseMimeType('AUDIO/OGG; codecs=opus')).toBe('audio/ogg');
    });
});

describe('choix du conteneur vidéo (T-210)', () => {
    it('préfère video/mp4, que toute la famille saura lire sans réencodage', () => {
        expect(
            pickMimeType('video', supporting(...PREFERRED_VIDEO_MIME_TYPES)),
        ).toBe('video/mp4');
    });

    it('accepte le WebM de Chrome Android, dont le serveur tirera un MP4', () => {
        expect(
            pickMimeType('video', supporting('video/webm;codecs=vp8,opus')),
        ).toBe('video/webm;codecs=vp8,opus');
    });

    it('ne propose jamais un conteneur audio quand on demande de la vidéo', () => {
        expect(pickMimeType('video', supporting(...PREFERRED_MIME_TYPES))).toBe(
            null,
        );
    });

    it('relit la nature dans le conteneur, jamais ailleurs', () => {
        expect(kindOfMime('video/webm;codecs=vp8,opus')).toBe('video');
        expect(kindOfMime('VIDEO/MP4')).toBe('video');
        expect(kindOfMime('audio/mp4')).toBe('audio');
        expect(kindOfMime('')).toBe('audio');
    });

    it('déclare la vidéo non supportée quand MediaRecorder manque', () => {
        expect(isRecordingSupported('video')).toBe(false);
    });
});
