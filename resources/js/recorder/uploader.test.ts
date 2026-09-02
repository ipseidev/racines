import 'fake-indexeddb/auto';

import { beforeEach, describe, expect, it, vi } from 'vitest';

import {
    appendChunk,
    clear,
    markPartUploaded,
    resetDatabase,
    resumeInfo,
    startDraft,
} from './draftStore';
import {
    backoffDelay,
    MAX_ATTEMPTS,
    splitIntoParts,
    uploadDraft,
    type UploaderPorts,
} from './uploader';

const STORY = 'a'.repeat(32);

const megabytes = (count: number) =>
    new Blob(['x'.repeat(count * 1024 * 1024)]);

function ports(overrides: Partial<UploaderPorts> = {}): UploaderPorts {
    return {
        initiate: vi.fn(async () => ({
            recordingId: 'rec-1',
            partSizeBytes: 5 * 1024 * 1024,
        })),
        openSegment: vi.fn(async () => ({ number: 2 })),
        sign: vi.fn(async (_r, segment, part) => ({
            url: `https://s3.test/${segment}/${part}`,
        })),
        put: vi.fn(async (url) => `etag-${url.split('/').slice(-2).join('-')}`),
        complete: vi.fn(async () => true),
        sleep: vi.fn(async () => {}),
        ...overrides,
    };
}

beforeEach(async () => {
    resetDatabase();
    await clear(STORY);
});

describe('découpe en parts', () => {
    it('découpe en parts de 5 Mio et laisse la dernière libre', () => {
        expect(splitIntoParts(megabytes(12)).map((part) => part.size)).toEqual([
            5 * 1024 * 1024,
            5 * 1024 * 1024,
            2 * 1024 * 1024,
        ]);
    });

    it('rend une seule part pour un petit enregistrement', () => {
        expect(
            splitIntoParts(new Blob(['bonjour'])).map((p) => p.size),
        ).toEqual([7]);
    });

    it('ne rend aucune part pour un blob vide', () => {
        expect(splitIntoParts(new Blob([]))).toEqual([]);
    });
});

describe('attente entre deux essais', () => {
    it('double à chaque échec : 1 s, 2 s, 4 s, 8 s', () => {
        expect([1, 2, 3, 4].map(backoffDelay)).toEqual([
            1000, 2000, 4000, 8000,
        ]);
    });
});

describe('envoi du brouillon', () => {
    it('envoie les parts dans l’ordre et conclut avec les ETags', async () => {
        const draft = await startDraft(STORY, 'audio/webm');
        await appendChunk(STORY, 1, 1, megabytes(7));

        const p = ports();
        const outcome = await uploadDraft(
            { ...draft, recordingId: 'rec-1' },
            p,
        );

        expect(outcome.confirmed).toBe(true);
        expect(p.sign).toHaveBeenCalledTimes(2);
        expect(p.complete).toHaveBeenCalledWith(
            'rec-1',
            [
                {
                    number: 1,
                    parts: [
                        { number: 1, etag: 'etag-1-1' },
                        { number: 2, etag: 'etag-1-2' },
                    ],
                },
            ],
            null,
        );
    });

    it('ouvre l’enregistrement quand le brouillon n’en connaît pas', async () => {
        const draft = await startDraft(STORY, 'audio/webm');
        await appendChunk(STORY, 1, 1, new Blob(['court']));

        const p = ports();
        await uploadDraft(draft, p);

        expect(p.initiate).toHaveBeenCalledWith('audio/webm');
    });

    it('ne renvoie pas les parts déjà déposées', async () => {
        await startDraft(STORY, 'audio/webm');
        await appendChunk(STORY, 1, 1, megabytes(7));
        await markPartUploaded(STORY, 1, 1, 'etag-connu');

        const info = await resumeInfo(STORY);
        const p = ports();

        await uploadDraft({ ...info!.draft, recordingId: 'rec-1' }, p);

        // Seule la seconde part est signée et envoyée.
        expect(p.sign).toHaveBeenCalledTimes(1);
        expect(p.complete).toHaveBeenCalledWith(
            'rec-1',
            [
                {
                    number: 1,
                    parts: [
                        { number: 1, etag: 'etag-connu' },
                        { number: 2, etag: 'etag-1-2' },
                    ],
                },
            ],
            null,
        );
    });

    it('réessaie une part en attendant de plus en plus, jusqu’à cinq fois', async () => {
        const draft = await startDraft(STORY, 'audio/webm');
        await appendChunk(STORY, 1, 1, new Blob(['court']));

        let attempts = 0;
        const sleep = vi.fn(async (_milliseconds: number) => {});

        const p = ports({
            sleep,
            put: vi.fn(async () => {
                attempts++;

                if (attempts < 3) {
                    throw new Error('réseau');
                }

                return 'etag-final';
            }),
        });

        const outcome = await uploadDraft(
            { ...draft, recordingId: 'rec-1' },
            p,
        );

        expect(outcome.confirmed).toBe(true);
        expect(attempts).toBe(3);
        expect(sleep.mock.calls.map((call) => call[0])).toEqual([1000, 2000]);
    });

    it('abandonne après cinq essais et laisse le brouillon intact', async () => {
        const draft = await startDraft(STORY, 'audio/webm');
        await appendChunk(STORY, 1, 1, new Blob(['court']));

        const p = ports({
            put: vi.fn(async () => {
                throw new Error('réseau coupé');
            }),
        });

        await expect(
            uploadDraft({ ...draft, recordingId: 'rec-1' }, p),
        ).rejects.toThrow('réseau coupé');

        expect(p.put).toHaveBeenCalledTimes(MAX_ATTEMPTS);
        // L'enregistrement reste sur le téléphone : rien n'est perdu.
        expect((await resumeInfo(STORY))?.chunkCount).toBe(1);
    });

    it('envoie chaque segment et ouvre les suivants côté serveur', async () => {
        const draft = await startDraft(STORY, 'audio/webm');
        await appendChunk(STORY, 1, 1, new Blob(['un']));
        await appendChunk(STORY, 2, 1, new Blob(['deux']));

        const p = ports();
        await uploadDraft({ ...draft, segments: 2, recordingId: 'rec-1' }, p);

        expect(p.openSegment).toHaveBeenCalledTimes(1);
        expect(p.complete).toHaveBeenCalledWith(
            'rec-1',
            [
                { number: 1, parts: [{ number: 1, etag: 'etag-1-1' }] },
                { number: 2, parts: [{ number: 1, etag: 'etag-2-1' }] },
            ],
            null,
        );
    });

    it('rend la progression en octets', async () => {
        const draft = await startDraft(STORY, 'audio/webm');
        await appendChunk(STORY, 1, 1, megabytes(7));

        const onProgress = vi.fn();
        await uploadDraft(
            { ...draft, recordingId: 'rec-1' },
            ports({ onProgress }),
        );

        const last = onProgress.mock.calls.at(-1);

        expect(last?.[0]).toBe(7 * 1024 * 1024);
        expect(last?.[1]).toBe(7 * 1024 * 1024);
    });

    it('rapporte l’absence de confirmation du serveur', async () => {
        const draft = await startDraft(STORY, 'audio/webm');
        await appendChunk(STORY, 1, 1, new Blob(['court']));

        const outcome = await uploadDraft(
            { ...draft, recordingId: 'rec-1' },
            ports({ complete: vi.fn(async () => false) }),
        );

        expect(outcome.confirmed).toBe(false);
    });
});
