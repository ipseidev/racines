import { useCallback, useEffect, useRef, useState } from 'react';

import {
    BAR_COUNT,
    createLevelMeter,
    type LevelMeter,
} from '@/recorder/levelMeter';
import {
    baseMimeType,
    isRecordingSupported,
    pickMimeType,
} from '@/recorder/mime';

export type LocalPhase =
    | 'idle'
    | 'recording'
    | 'ready'
    | 'unsupported'
    | 'refused';

type Options = {
    /** L'arrêt ferme, en secondes. */
    maxSeconds: number;
    /** La taille d'une tranche, en millisecondes. */
    segmentMilliseconds: number;
};

/**
 * Enregistrer sans rien confier.
 *
 * Le contraire exact du magnétophone du bloc 04, et c'est tout son intérêt :
 * **rien ne part et rien ne reste**. Pas d'appel réseau, pas d'écriture dans
 * IndexedDB, pas de brouillon. Les tranches restent en mémoire, l'URL de
 * lecture est révoquée à la fermeture, et les pistes du micro sont relâchées
 * dès l'arrêt — la pastille rouge du navigateur s'éteint, ce qui est la seule
 * preuve visible que l'écoute a cessé.
 *
 * Deux écrans s'en servent, et pour la même raison : quelqu'un essaie.
 * L'acheteur sur `/essai`, qui se demande si sa mère saura s'en servir ; la
 * narratrice à son tout premier lien, à qui l'on propose un tour de chauffe
 * avant la vraie question. Une seule mécanique, parce que deux copies de
 * « on relâche bien le micro » finiraient par diverger, et que celle qui
 * oublie laisse une pastille rouge allumée chez une personne de 80 ans.
 *
 * La permission accordée pendant l'essai vaut pour la suite de la page : sur
 * le lien de la narratrice, c'est même le bénéfice principal — la question du
 * micro est réglée avant que la vraie question ne se pose.
 */
export function useLocalRecorder({ maxSeconds, segmentMilliseconds }: Options) {
    const [phase, setPhase] = useState<LocalPhase>('idle');
    const [seconds, setSeconds] = useState(0);
    const [levels, setLevels] = useState<number[]>(
        Array<number>(BAR_COUNT).fill(0),
    );
    const [playbackUrl, setPlaybackUrl] = useState<string | null>(null);

    const recorder = useRef<MediaRecorder | null>(null);
    const stream = useRef<MediaStream | null>(null);
    const meter = useRef<LevelMeter | null>(null);
    const chunks = useRef<Blob[]>([]);
    const tick = useRef<number | null>(null);
    const pulse = useRef<number | null>(null);

    const release = useCallback(() => {
        if (tick.current !== null) {
            window.clearInterval(tick.current);
            tick.current = null;
        }

        if (pulse.current !== null) {
            window.clearInterval(pulse.current);
            pulse.current = null;
        }

        meter.current?.stop();
        meter.current = null;
        stream.current?.getTracks().forEach((track) => track.stop());
        stream.current = null;
        recorder.current = null;
        chunks.current = [];
    }, []);

    // L'effacement à la fermeture, et il compte autant que le reste : un essai
    // ne laisse rien, même pas une URL d'objet dans l'onglet.
    useEffect(
        () => () => {
            release();

            if (playbackUrl !== null) {
                URL.revokeObjectURL(playbackUrl);
            }
        },
        [playbackUrl, release],
    );

    useEffect(() => {
        if (!isRecordingSupported()) {
            setPhase('unsupported');
        }
    }, []);

    const stop = useCallback(() => {
        const instance = recorder.current;

        if (instance === null || instance.state === 'inactive') {
            return;
        }

        instance.onstop = () => {
            const blob = new Blob(chunks.current, {
                type: baseMimeType(instance.mimeType),
            });

            setPlaybackUrl(URL.createObjectURL(blob));
            setPhase('ready');
            release();
        };

        instance.stop();
    }, [release]);

    const start = useCallback(async () => {
        const mime = pickMimeType();

        if (mime === null) {
            setPhase('unsupported');

            return;
        }

        try {
            stream.current = await navigator.mediaDevices.getUserMedia({
                audio: true,
            });
        } catch {
            setPhase('refused');

            return;
        }

        const instance = new MediaRecorder(stream.current, { mimeType: mime });

        chunks.current = [];
        instance.ondataavailable = (event: BlobEvent) => {
            if (event.data.size > 0) {
                chunks.current.push(event.data);
            }
        };

        recorder.current = instance;
        instance.start(segmentMilliseconds);

        setSeconds(0);
        setPhase('recording');

        meter.current = createLevelMeter(stream.current);

        // Seize images par seconde : l'œil lit le mouvement, et un vieux
        // téléphone ne passe pas sa minute à recalculer douze barres.
        pulse.current = window.setInterval(() => {
            setLevels(meter.current?.levels() ?? []);
        }, 60);

        tick.current = window.setInterval(() => {
            setSeconds((previous) => {
                const next = previous + 1;

                if (next >= maxSeconds) {
                    stop();
                }

                return next;
            });
        }, 1000);
    }, [maxSeconds, segmentMilliseconds, stop]);

    const again = useCallback(() => {
        if (playbackUrl !== null) {
            URL.revokeObjectURL(playbackUrl);
        }

        setPlaybackUrl(null);
        setSeconds(0);
        setPhase('idle');
    }, [playbackUrl]);

    return { phase, seconds, levels, playbackUrl, start, stop, again };
}
