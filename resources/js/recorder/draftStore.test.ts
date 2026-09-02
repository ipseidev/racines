import 'fake-indexeddb/auto';

import { beforeEach, describe, expect, it } from 'vitest';

import {
    appendChunk,
    blobForSegment,
    clear,
    listChunks,
    markPartUploaded,
    openSegment,
    parseUploadedParts,
    rememberRecordingId,
    resetDatabase,
    resumeInfo,
    startDraft,
} from './draftStore';

const STORY = 'e3b0c44298fc1c149afbf4c8996fb924';

const chunk = (text: string) => new Blob([text], { type: 'audio/webm' });

beforeEach(async () => {
    resetDatabase();
    await clear(STORY);
});

describe('brouillon local', () => {
    it('écrit les tranches et les rend dans l’ordre', async () => {
        await startDraft(STORY, 'audio/webm');
        await appendChunk(STORY, 1, 1, chunk('a'));
        await appendChunk(STORY, 1, 3, chunk('c'));
        await appendChunk(STORY, 1, 2, chunk('b'));

        const chunks = await listChunks(STORY);

        expect(chunks.map((c) => c.index)).toEqual([1, 2, 3]);
    });

    it('range les segments avant les index', async () => {
        await startDraft(STORY, 'audio/webm');
        await appendChunk(STORY, 2, 1, chunk('c'));
        await appendChunk(STORY, 1, 2, chunk('b'));
        await appendChunk(STORY, 1, 1, chunk('a'));

        const chunks = await listChunks(STORY);

        expect(chunks.map((c) => [c.segment, c.index])).toEqual([
            [1, 1],
            [1, 2],
            [2, 1],
        ]);
    });

    it('recompose un segment en un seul blob', async () => {
        await startDraft(STORY, 'audio/webm');
        await appendChunk(STORY, 1, 1, chunk('abc'));
        await appendChunk(STORY, 1, 2, chunk('de'));

        const blob = await blobForSegment(STORY, 1);

        expect(blob?.size).toBe(5);
        expect(blob?.type).toBe('audio/webm');
    });

    it('ouvre un segment de plus après une interruption', async () => {
        await startDraft(STORY, 'audio/webm');

        expect(await openSegment(STORY)).toBe(2);
        expect(await openSegment(STORY)).toBe(3);
    });

    it('note les parts déjà envoyées, sans doublon', async () => {
        await startDraft(STORY, 'audio/webm');
        await markPartUploaded(STORY, 1, 1, 'etag-1');
        await markPartUploaded(STORY, 1, 1, 'etag-1');
        await markPartUploaded(STORY, 1, 2, 'etag-2');

        const info = await resumeInfo(STORY);

        expect(info).toBeNull(); // aucune tranche : rien à reprendre

        await appendChunk(STORY, 1, 1, chunk('a'));

        const withChunks = await resumeInfo(STORY);

        expect(withChunks?.draft.uploadedParts).toHaveLength(2);
        expect(parseUploadedParts(withChunks!.draft)).toEqual([
            { segment: 1, part: 1, etag: 'etag-1' },
            { segment: 1, part: 2, etag: 'etag-2' },
        ]);
    });

    it('retrouve le brouillon après un rechargement de page', async () => {
        await startDraft(STORY, 'audio/webm');
        await rememberRecordingId(STORY, 'rec-1');
        await appendChunk(STORY, 1, 1, chunk('bonjour'));

        // Nouvelle instance : c'est ce que fait un rechargement.
        resetDatabase();

        const info = await resumeInfo(STORY);

        expect(info?.chunkCount).toBe(1);
        expect(info?.bytes).toBe(7);
        expect(info?.draft.recordingId).toBe('rec-1');
        expect(info?.draft.mime).toBe('audio/webm');
    });

    it('ne propose rien quand il n’y a pas de brouillon', async () => {
        expect(await resumeInfo('inconnu')).toBeNull();
    });

    it('efface tout à la demande', async () => {
        await startDraft(STORY, 'audio/webm');
        await appendChunk(STORY, 1, 1, chunk('a'));
        await clear(STORY);

        expect(await resumeInfo(STORY)).toBeNull();
        expect(await listChunks(STORY)).toEqual([]);
    });
});
