import Dexie, { type Table } from 'dexie';

/**
 * Le brouillon local, sur le téléphone du narrateur.
 *
 * C'est la pièce qui répond au risque technique n°1 du dossier : un appel
 * entrant, une mise en veille ou une purge d'onglet iOS ne doit pas coûter
 * l'histoire. Chaque tranche de cinq secondes rendue par `MediaRecorder` est
 * écrite ici avant tout envoi, donc avant que quoi que ce soit puisse échouer.
 *
 * Les brouillons sont indexés par `storyRef`, une empreinte de l'identifiant
 * d'histoire : l'identifiant réel ne descend pas jusqu'au navigateur.
 */
export type Draft = {
    storyRef: string;
    mime: string;
    segments: number;
    createdAt: number;
    recordingId: string | null;
    uploadedParts: string[];
};

/**
 * Une tranche stockée en **octets bruts**, pas en `Blob`.
 *
 * C'est délibéré : Safari iOS invalide les références de `Blob` conservées
 * dans IndexedDB quand il purge un onglet — exactement le scénario auquel ce
 * brouillon doit survivre. Un `ArrayBuffer` traverse la purge intact.
 */
export type Chunk = {
    id?: number;
    storyRef: string;
    segment: number;
    index: number;
    bytes: ArrayBuffer;
    size: number;
};

export type ResumeInfo = {
    draft: Draft;
    chunkCount: number;
    bytes: number;
};

class RecorderDatabase extends Dexie {
    declare drafts: Table<Draft, string>;

    declare chunks: Table<Chunk, number>;

    constructor() {
        super('recorder');

        this.version(1).stores({
            drafts: 'storyRef',
            chunks: '++id, [storyRef+segment+index], storyRef',
        });
    }
}

let database: RecorderDatabase | null = null;

function db(): RecorderDatabase {
    database ??= new RecorderDatabase();

    return database;
}

/** Réinitialise la connexion — utile entre deux tests. */
export function resetDatabase(): void {
    database = null;
}

export async function startDraft(
    storyRef: string,
    mime: string,
): Promise<Draft> {
    const draft: Draft = {
        storyRef,
        mime,
        segments: 1,
        createdAt: Date.now(),
        recordingId: null,
        uploadedParts: [],
    };

    await db().drafts.put(draft);

    return draft;
}

export async function appendChunk(
    storyRef: string,
    segment: number,
    index: number,
    blob: Blob,
): Promise<void> {
    const bytes = await blob.arrayBuffer();

    await db().chunks.put({
        storyRef,
        segment,
        index,
        bytes,
        size: bytes.byteLength,
    });
}

export async function listChunks(
    storyRef: string,
    segment?: number,
): Promise<Chunk[]> {
    const all = await db().chunks.where('storyRef').equals(storyRef).toArray();

    return all
        .filter((chunk) => segment === undefined || chunk.segment === segment)
        .sort((a, b) => a.segment - b.segment || a.index - b.index);
}

export async function blobForSegment(
    storyRef: string,
    segment: number,
): Promise<Blob | null> {
    const chunks = await listChunks(storyRef, segment);

    if (chunks.length === 0) {
        return null;
    }

    const draft = await db().drafts.get(storyRef);

    return new Blob(
        chunks.map((chunk) => chunk.bytes),
        { type: draft?.mime ?? 'application/octet-stream' },
    );
}

export async function openSegment(storyRef: string): Promise<number> {
    const draft = await db().drafts.get(storyRef);

    if (draft === undefined) {
        throw new Error(`Aucun brouillon pour ${storyRef}.`);
    }

    const segments = draft.segments + 1;
    await db().drafts.update(storyRef, { segments });

    return segments;
}

export async function rememberRecordingId(
    storyRef: string,
    recordingId: string,
): Promise<void> {
    await db().drafts.update(storyRef, { recordingId });
}

/** Une part envoyée est notée : la reprise ne la renvoie pas. */
export async function markPartUploaded(
    storyRef: string,
    segment: number,
    part: number,
    etag: string,
): Promise<void> {
    const draft = await db().drafts.get(storyRef);

    if (draft === undefined) {
        return;
    }

    const key = `${segment}:${part}:${etag}`;
    const uploadedParts = draft.uploadedParts.includes(key)
        ? draft.uploadedParts
        : [...draft.uploadedParts, key];

    await db().drafts.update(storyRef, { uploadedParts });
}

export type UploadedPart = { segment: number; part: number; etag: string };

export function parseUploadedParts(draft: Draft): UploadedPart[] {
    return draft.uploadedParts.flatMap((entry) => {
        const [segment, part, ...etag] = entry.split(':');

        if (segment === undefined || part === undefined || etag.length === 0) {
            return [];
        }

        return [
            {
                segment: Number(segment),
                part: Number(part),
                etag: etag.join(':'),
            },
        ];
    });
}

export async function resumeInfo(storyRef: string): Promise<ResumeInfo | null> {
    const draft = await db().drafts.get(storyRef);

    if (draft === undefined) {
        return null;
    }

    const chunks = await listChunks(storyRef);

    if (chunks.length === 0) {
        return null;
    }

    return {
        draft,
        chunkCount: chunks.length,
        bytes: chunks.reduce((total, chunk) => total + chunk.size, 0),
    };
}

export async function clear(storyRef: string): Promise<void> {
    await db().chunks.where('storyRef').equals(storyRef).delete();
    await db().drafts.delete(storyRef);
}

/**
 * Place restante. On avertit sous 50 Mo sans bloquer : un narrateur ne doit
 * jamais être empêché de parler par une estimation.
 */
export async function hasRoom(
    minimumBytes = 50 * 1024 * 1024,
): Promise<boolean> {
    const estimate = await globalThis.navigator?.storage?.estimate?.();

    if (estimate?.quota === undefined || estimate.usage === undefined) {
        return true;
    }

    return estimate.quota - estimate.usage >= minimumBytes;
}
