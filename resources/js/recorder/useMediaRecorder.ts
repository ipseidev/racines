import { useCallback, useRef, useState } from 'react';

import {
    appendChunk,
    openSegment as openDraftSegment,
    startDraft,
} from './draftStore';
import { baseMimeType, pickMimeType, type RecordingKind } from './mime';

/**
 * `MediaRecorder`, branché sur le brouillon local.
 *
 * La règle qui gouverne tout ce fichier : **aucune tranche ne vit uniquement
 * en mémoire**. Chaque `dataavailable` — toutes les cinq secondes — est écrit
 * sur le téléphone avant toute autre chose. Un appel entrant, une veille ou
 * une purge d'onglet arrive donc toujours après que ce qui a été dit est en
 * sécurité. La vidéo (T-210) ne change rien à cette règle ; elle la rend
 * seulement plus lourde, d'où le débit imposé.
 */
export type VideoConstraints = {
    bitsPerSecond: number;
    audioBitsPerSecond: number;
    height: number;
};

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
    kind: RecordingKind = 'audio',
    video?: VideoConstraints,
): MediaRecorderHandle {
    const recorder = useRef<MediaRecorder | null>(null);
    const streamRef = useRef<MediaStream | null>(null);
    const segment = useRef(1);
    const chunkIndex = useRef(0);
    const [mime, setMime] = useState<string | null>(null);
    // L'aperçu de soi a besoin du flux dès qu'il existe. Un `useRef` ne
    // rendrait pas, et la personne se verrait dans un rectangle noir.
    const [stream, setStream] = useState<MediaStream | null>(null);

    const requestPermission = useCallback(async (): Promise<boolean> => {
        try {
            const captured = await navigator.mediaDevices.getUserMedia({
                audio: {
                    echoCancellation: true,
                    noiseSuppression: true,
                    autoGainControl: true,
                },
                // La caméra frontale et une définition plafonnée : personne ne
                // regarde un souvenir de famille en 4K, et chaque mégaoctet
                // de trop est un mégaoctet qu'une 4G de campagne n'enverra pas.
                ...(kind === 'video'
                    ? {
                          video: {
                              facingMode: 'user',
                              width: { ideal: 1280 },
                              height: { ideal: video?.height ?? 720 },
                              frameRate: { ideal: 30, max: 30 },
                          },
                      }
                    : {}),
            });

            streamRef.current = captured;
            setStream(captured);

            return true;
        } catch {
            return false;
        }
    }, [kind, video?.height]);

    const attach = useCallback(
        (chosenMime: string) => {
            const stream = streamRef.current;

            if (stream === null) {
                throw new Error('Aucun flux micro.');
            }

            const instance = new MediaRecorder(stream, {
                mimeType: chosenMime,
                // Le débit n'est imposé que pour la vidéo : laissé libre,
                // Chrome monte à cinq mégabits et le même récit devient
                // inenvoyable depuis une maison de campagne.
                ...(kind === 'video' && video !== undefined
                    ? {
                          videoBitsPerSecond: video.bitsPerSecond,
                          audioBitsPerSecond: video.audioBitsPerSecond,
                      }
                    : {}),
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
        [kind, storyRef, video],
    );

    const start = useCallback(async () => {
        const chosen = pickMimeType(kind);

        if (chosen === null) {
            throw new Error('Ce navigateur ne sait pas enregistrer.');
        }

        setMime(baseMimeType(chosen));
        segment.current = 1;
        chunkIndex.current = 0;

        await startDraft(storyRef, baseMimeType(chosen));

        attach(chosen).start(timesliceMs);
    }, [attach, kind, storyRef, timesliceMs]);

    const startNewSegment = useCallback(async () => {
        const chosen = pickMimeType(kind);

        if (chosen === null) {
            return;
        }

        segment.current = await openDraftSegment(storyRef);
        chunkIndex.current = 0;

        attach(chosen).start(timesliceMs);
    }, [attach, kind, storyRef, timesliceMs]);

    const pause = useCallback(() => {
        if (recorder.current?.state === 'recording') {
            // Le morceau en cours part tout de suite vers le brouillon : c'est
            // ce qui permet de se réécouter pendant la pause (T-139), au lieu
            // d'attendre la prochaine tranche.
            recorder.current.requestData();
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
        setStream(null);
        recorder.current = null;
    }, []);

    return {
        mime,
        stream,
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
