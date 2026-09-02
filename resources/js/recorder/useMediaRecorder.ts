import { useCallback, useRef, useState } from 'react';

import {
    appendChunk,
    openSegment as openDraftSegment,
    startDraft,
} from './draftStore';
import { baseMimeType, pickMimeType } from './mime';

/**
 * `MediaRecorder`, branché sur le brouillon local.
 *
 * La règle qui gouverne tout ce fichier : **aucune tranche audio ne vit
 * uniquement en mémoire**. Chaque `dataavailable` — toutes les cinq secondes —
 * est écrit sur le téléphone avant toute autre chose. Un appel entrant, une
 * veille ou une purge d'onglet arrive donc toujours après que ce qui a été dit
 * est en sécurité.
 */
export type MediaRecorderHandle = {
    mime: string | null;
    stream: MediaStream | null;
    requestPermission: () => Promise<boolean>;
    start: () => Promise<void>;
    pause: () => void;
    resume: () => void;
    startNewSegment: () => Promise<void>;
    stop: () => Promise<void>;
    isInactive: () => boolean;
    release: () => void;
};

export function useMediaRecorder(
    storyRef: string,
    timesliceMs: number,
): MediaRecorderHandle {
    const recorder = useRef<MediaRecorder | null>(null);
    const streamRef = useRef<MediaStream | null>(null);
    const segment = useRef(1);
    const chunkIndex = useRef(0);
    const [mime, setMime] = useState<string | null>(null);

    const requestPermission = useCallback(async (): Promise<boolean> => {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({
                audio: {
                    echoCancellation: true,
                    noiseSuppression: true,
                    autoGainControl: true,
                },
            });

            streamRef.current = stream;

            return true;
        } catch {
            return false;
        }
    }, []);

    const attach = useCallback(
        (chosenMime: string) => {
            const stream = streamRef.current;

            if (stream === null) {
                throw new Error('Aucun flux micro.');
            }

            const instance = new MediaRecorder(stream, {
                mimeType: chosenMime,
            });

            instance.ondataavailable = (event: BlobEvent) => {
                if (event.data.size === 0) {
                    return;
                }

                chunkIndex.current += 1;

                // Écriture avant tout : c'est ce qui rend l'interruption
                // survivable.
                void appendChunk(
                    storyRef,
                    segment.current,
                    chunkIndex.current,
                    event.data,
                );
            };

            recorder.current = instance;

            return instance;
        },
        [storyRef],
    );

    const start = useCallback(async () => {
        const chosen = pickMimeType();

        if (chosen === null) {
            throw new Error('Ce navigateur ne sait pas enregistrer.');
        }

        setMime(baseMimeType(chosen));
        segment.current = 1;
        chunkIndex.current = 0;

        await startDraft(storyRef, baseMimeType(chosen));

        attach(chosen).start(timesliceMs);
    }, [attach, storyRef, timesliceMs]);

    const startNewSegment = useCallback(async () => {
        const chosen = pickMimeType();

        if (chosen === null) {
            return;
        }

        segment.current = await openDraftSegment(storyRef);
        chunkIndex.current = 0;

        attach(chosen).start(timesliceMs);
    }, [attach, storyRef, timesliceMs]);

    const pause = useCallback(() => {
        if (recorder.current?.state === 'recording') {
            recorder.current.pause();
        }
    }, []);

    const resume = useCallback(() => {
        if (recorder.current?.state === 'paused') {
            recorder.current.resume();
        }
    }, []);

    const stop = useCallback(async () => {
        const instance = recorder.current;

        if (instance === null || instance.state === 'inactive') {
            return;
        }

        await new Promise<void>((resolve) => {
            instance.onstop = () => resolve();
            instance.stop();
        });
    }, []);

    const isInactive = useCallback(
        () =>
            recorder.current === null || recorder.current.state === 'inactive',
        [],
    );

    const release = useCallback(() => {
        streamRef.current?.getTracks().forEach((track) => track.stop());
        streamRef.current = null;
        recorder.current = null;
    }, []);

    return {
        mime,
        stream: streamRef.current,
        requestPermission,
        start,
        pause,
        resume,
        startNewSegment,
        stop,
        isInactive,
        release,
    };
}
