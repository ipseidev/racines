import { describe, expect, it } from 'vitest';

import { baseMimeType, pickMimeType, PREFERRED_MIME_TYPES } from './mime';

const supporting =
    (...supported: string[]) =>
    (mimeType: string) =>
        supported.includes(mimeType);

describe('choix du conteneur audio', () => {
    it('préfère audio/mp4, le seul que Safari iOS sait produire', () => {
        expect(pickMimeType(supporting(...PREFERRED_MIME_TYPES))).toBe(
            'audio/mp4',
        );
    });

    it('retombe sur Opus quand mp4 est absent', () => {
        expect(
            pickMimeType(supporting('audio/webm;codecs=opus', 'audio/webm')),
        ).toBe('audio/webm;codecs=opus');
    });

    it('accepte webm nu en dernier recours', () => {
        expect(pickMimeType(supporting('audio/webm'))).toBe('audio/webm');
    });

    it('accepte ogg quand c’est tout ce qu’il y a', () => {
        expect(pickMimeType(supporting('audio/ogg;codecs=opus'))).toBe(
            'audio/ogg;codecs=opus',
        );
    });

    it('rend null quand le navigateur ne sait rien produire', () => {
        expect(pickMimeType(supporting())).toBeNull();
        expect(pickMimeType(undefined)).toBeNull();
    });

    it('déclare au serveur un type sans paramètre de codec', () => {
        expect(baseMimeType('audio/webm;codecs=opus')).toBe('audio/webm');
        expect(baseMimeType('audio/mp4')).toBe('audio/mp4');
        expect(baseMimeType('AUDIO/OGG; codecs=opus')).toBe('audio/ogg');
    });
});
