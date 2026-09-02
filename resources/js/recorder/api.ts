import type { UploaderPorts } from './uploader';

/**
 * Les appels au serveur pendant un envoi.
 *
 * Regroupés ici pour que `uploader.ts` reste testable sans réseau : il ne
 * connaît que des fonctions, jamais `fetch`.
 */
function csrfToken(): string | undefined {
    return document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
        ?.content;
}

async function post<T>(path: string, body: unknown): Promise<T> {
    const token = csrfToken();

    const response = await fetch(path, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            ...(token === undefined ? {} : { 'X-CSRF-TOKEN': token }),
        },
        body: JSON.stringify(body),
    });

    if (!response.ok && response.status !== 422) {
        throw new Error(`Le serveur a répondu ${response.status}.`);
    }

    return (await response.json()) as T;
}

export function createUploaderPorts(basePath: string): UploaderPorts {
    return {
        initiate: async (mime) => {
            const json = await post<{
                recording_id: string;
                part_size_bytes: number;
            }>(`${basePath}/recordings`, { mime, expected_bytes: 1 });

            return {
                recordingId: json.recording_id,
                partSizeBytes: json.part_size_bytes,
            };
        },

        openSegment: async (recordingId) =>
            post<{ number: number }>(
                `${basePath}/recordings/${recordingId}/segments`,
                {},
            ),

        sign: async (recordingId, segment, part) =>
            post<{ url: string }>(
                `${basePath}/recordings/${recordingId}/segments/${segment}/parts/${part}/sign`,
                {},
            ),

        put: async (url, body) => {
            const response = await fetch(url, { method: 'PUT', body });

            if (!response.ok) {
                throw new Error(`Le stockage a répondu ${response.status}.`);
            }

            const etag = response.headers.get('ETag');

            if (etag === null) {
                throw new Error('Le stockage n’a pas rendu d’ETag.');
            }

            return etag.replaceAll('"', '');
        },

        complete: async (recordingId, segments, clientDurationSeconds) => {
            const json = await post<{ confirmed: boolean }>(
                `${basePath}/recordings/${recordingId}/complete`,
                {
                    segments,
                    ...(clientDurationSeconds === null
                        ? {}
                        : { client_duration_seconds: clientDurationSeconds }),
                },
            );

            return json.confirmed === true;
        },
    };
}
