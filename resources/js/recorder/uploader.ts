import {
    blobForSegment,
    markPartUploaded,
    parseUploadedParts,
    type Draft,
    type UploadedPart,
} from './draftStore';

/**
 * Envoi de l'audio vers le stockage, part par part, reprenable.
 *
 * Trois propriétés font la différence entre « ça marche au bureau » et « ça
 * marche pour une dame de 82 ans en 4G » :
 *
 *  - **les parts sont indépendantes** : une coupure ne coûte que la part en
 *    cours, jamais l'enregistrement ;
 *  - **les parts déjà déposées sont notées sur le téléphone** : une reprise ne
 *    renvoie pas ce qui est passé ;
 *  - **le renvoi attend de plus en plus longtemps** : sur un réseau qui rame,
 *    marteler ne fait qu'aggraver.
 */
export const PART_SIZE_BYTES = 5 * 1024 * 1024;

export const MAX_ATTEMPTS = 5;

export const CONCURRENCY = 2;

/** 1 s, 2 s, 4 s, 8 s : on double, on ne martèle pas. */
export function backoffDelay(attempt: number): number {
    return 2 ** (attempt - 1) * 1000;
}

export type SignedPart = { url: string };

export type UploaderPorts = {
    /** Ouvre l'enregistrement côté serveur et rend son identifiant. */
    initiate: (
        mime: string,
    ) => Promise<{ recordingId: string; partSizeBytes: number }>;
    /** Ouvre un segment supplémentaire. */
    openSegment: (recordingId: string) => Promise<{ number: number }>;
    /** Demande une URL présignée pour une part. */
    sign: (
        recordingId: string,
        segment: number,
        part: number,
    ) => Promise<SignedPart>;
    /** Dépose une part et rend son ETag. */
    put: (url: string, body: Blob) => Promise<string>;
    /** Conclut l'envoi ; rend vrai seulement si le serveur a confirmé. */
    complete: (
        recordingId: string,
        segments: Array<{
            number: number;
            parts: Array<{ number: number; etag: string }>;
        }>,
        clientDurationSeconds: number | null,
    ) => Promise<boolean>;
    sleep?: (milliseconds: number) => Promise<void>;
    onProgress?: (sent: number, total: number) => void;
};

export function splitIntoParts(blob: Blob, partSize = PART_SIZE_BYTES): Blob[] {
    if (blob.size === 0) {
        return [];
    }

    const parts: Blob[] = [];

    for (let offset = 0; offset < blob.size; offset += partSize) {
        parts.push(blob.slice(offset, Math.min(offset + partSize, blob.size)));
    }

    return parts;
}

const defaultSleep = (milliseconds: number) =>
    new Promise<void>((resolve) => {
        setTimeout(resolve, milliseconds);
    });

/**
 * Dépose une part, en réessayant avec une attente qui double.
 */
async function putWithRetry(
    ports: UploaderPorts,
    recordingId: string,
    segment: number,
    part: number,
    body: Blob,
): Promise<string> {
    const sleep = ports.sleep ?? defaultSleep;
    let lastError: unknown = null;

    for (let attempt = 1; attempt <= MAX_ATTEMPTS; attempt++) {
        try {
            const signed = await ports.sign(recordingId, segment, part);

            return await ports.put(signed.url, body);
        } catch (error) {
            lastError = error;

            if (attempt < MAX_ATTEMPTS) {
                await sleep(backoffDelay(attempt));
            }
        }
    }

    throw lastError instanceof Error
        ? lastError
        : new Error(
              `Envoi de la part ${part} du segment ${segment} impossible.`,
          );
}

/** Exécute les tâches deux par deux, dans l'ordre de départ. */
async function inBatches<T>(
    tasks: Array<() => Promise<T>>,
    size = CONCURRENCY,
): Promise<T[]> {
    const results: T[] = [];

    for (let index = 0; index < tasks.length; index += size) {
        const batch = tasks.slice(index, index + size);
        results.push(...(await Promise.all(batch.map((task) => task()))));
    }

    return results;
}

export type UploadOutcome = { confirmed: boolean; recordingId: string };

export async function uploadDraft(
    draft: Draft,
    ports: UploaderPorts,
    clientDurationSeconds: number | null = null,
): Promise<UploadOutcome> {
    const recordingId =
        draft.recordingId ?? (await ports.initiate(draft.mime)).recordingId;

    const already: UploadedPart[] = parseUploadedParts(draft);
    const segments: Array<{
        number: number;
        parts: Array<{ number: number; etag: string }>;
    }> = [];

    let sentBytes = 0;
    const totalBytes = await totalDraftBytes(draft);

    for (let segment = 1; segment <= draft.segments; segment++) {
        const blob = await blobForSegment(draft.storyRef, segment);

        if (blob === null) {
            continue;
        }

        if (segment > 1 && already.every((part) => part.segment !== segment)) {
            await ports.openSegment(recordingId);
        }

        const chunks = splitIntoParts(blob);
        const parts: Array<{ number: number; etag: string }> = [];

        const tasks = chunks.map((body, index) => async () => {
            const partNumber = index + 1;
            const known = already.find(
                (part) => part.segment === segment && part.part === partNumber,
            );

            // Une part déjà déposée n'est pas renvoyée : c'est tout l'intérêt
            // d'en avoir gardé la trace sur le téléphone.
            const etag =
                known?.etag ??
                (await putWithRetry(
                    ports,
                    recordingId,
                    segment,
                    partNumber,
                    body,
                ));

            if (known === undefined) {
                await markPartUploaded(
                    draft.storyRef,
                    segment,
                    partNumber,
                    etag,
                );
            }

            sentBytes += body.size;
            ports.onProgress?.(sentBytes, totalBytes);

            return { number: partNumber, etag };
        });

        parts.push(...(await inBatches(tasks)));
        segments.push({ number: segment, parts });
    }

    const confirmed = await ports.complete(
        recordingId,
        segments,
        clientDurationSeconds,
    );

    return { confirmed, recordingId };
}

async function totalDraftBytes(draft: Draft): Promise<number> {
    let total = 0;

    for (let segment = 1; segment <= draft.segments; segment++) {
        const blob = await blobForSegment(draft.storyRef, segment);
        total += blob?.size ?? 0;
    }

    return total;
}
