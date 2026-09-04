import { Head, Link } from '@inertiajs/react';
import { useCallback, useEffect, useRef, useState } from 'react';

import { useT } from '@/hooks/useT';
import {
    baseMimeType,
    isRecordingSupported,
    pickMimeType,
} from '@/recorder/mime';

type Props = {
    limits: {
        demoSeconds: number;
        segmentMilliseconds: number;
        acceptedMimes: string[];
    };
};

type Phase = 'idle' | 'recording' | 'ready' | 'unsupported' | 'refused';

/**
 * L'essai en soixante secondes.
 *
 * Ce que cette page **ne fait pas** est son intérêt : rien ne part. Pas
 * d'appel réseau, pas d'écriture dans IndexedDB, pas de brouillon. Les
 * tranches restent en mémoire et l'URL de lecture est révoquée à la
 * fermeture. Le recorder du bloc 04, lui, écrit chaque tranche sur le
 * téléphone avant tout — c'est ce qui rend un appel entrant survivable — mais
 * ici il n'y a rien à sauver : quelqu'un qui essaie le service ne nous a rien
 * confié, et ne doit rien laisser derrière lui.
 *
 * C'est aussi ce que le test bout en bout vérifie : zéro requête vers
 * `/recordings`.
 */
export default function Demo({ limits }: Props) {
    const t = useT();

    const [phase, setPhase] = useState<Phase>('idle');
    const [seconds, setSeconds] = useState(0);
    const [playbackUrl, setPlaybackUrl] = useState<string | null>(null);

    const recorder = useRef<MediaRecorder | null>(null);
    const stream = useRef<MediaStream | null>(null);
    const chunks = useRef<Blob[]>([]);
    const tick = useRef<number | null>(null);

    const release = useCallback(() => {
        if (tick.current !== null) {
            window.clearInterval(tick.current);
            tick.current = null;
        }

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

        const instance = new MediaRecorder(stream.current, {
            mimeType: mime,
        });

        chunks.current = [];
        instance.ondataavailable = (event: BlobEvent) => {
            if (event.data.size > 0) {
                chunks.current.push(event.data);
            }
        };

        recorder.current = instance;
        instance.start(limits.segmentMilliseconds);

        setSeconds(0);
        setPhase('recording');

        tick.current = window.setInterval(() => {
            setSeconds((previous) => {
                const next = previous + 1;

                if (next >= limits.demoSeconds) {
                    stop();
                }

                return next;
            });
        }, 1000);
    }, [limits.demoSeconds, limits.segmentMilliseconds, stop]);

    const again = () => {
        if (playbackUrl !== null) {
            URL.revokeObjectURL(playbackUrl);
        }

        setPlaybackUrl(null);
        setPhase('idle');
    };

    return (
        <div className="mx-auto w-full max-w-3xl px-6 py-8 text-[1.125rem] leading-relaxed">
            <Head title={t('public.demo.title')} />

            <h1 className="font-display text-3xl leading-tight font-semibold">
                {t('public.demo.title')}
            </h1>

            <p className="mt-4">{t('public.demo.body')}</p>

            <p className="text-brand-muted mt-2 text-base">
                {t('public.demo.nothing_sent')}
            </p>

            {phase === 'unsupported' && (
                <p role="status" className="mt-8">
                    {t('public.demo.unsupported')}
                </p>
            )}

            {phase === 'refused' && (
                <p role="status" className="mt-8">
                    {t('public.demo.refused')}
                </p>
            )}

            {phase === 'idle' && (
                <button
                    type="button"
                    onClick={() => void start()}
                    className="bg-brand-accent text-brand-accent-foreground hover:bg-brand-accent-deep mt-8 min-h-[3.5rem] rounded-md px-8 py-4 text-lg font-semibold"
                >
                    {t('public.demo.start')}
                </button>
            )}

            {phase === 'recording' && (
                <div className="mt-8">
                    <p role="status" aria-live="polite" className="text-lg">
                        {t('public.demo.recording', {
                            seconds: String(limits.demoSeconds - seconds),
                        })}
                    </p>

                    <button
                        type="button"
                        onClick={stop}
                        className="bg-brand-accent text-brand-accent-foreground hover:bg-brand-accent-deep mt-4 min-h-[3.5rem] rounded-md px-8 py-4 text-lg font-semibold"
                    >
                        {t('public.demo.stop')}
                    </button>
                </div>
            )}

            {phase === 'ready' && playbackUrl !== null && (
                <div className="mt-8">
                    <p className="text-lg">{t('public.demo.ready')}</p>

                    {/* eslint-disable-next-line jsx-a11y/media-has-caption */}
                    <audio
                        src={playbackUrl}
                        controls
                        className="mt-4 w-full"
                        aria-label={t('public.demo.playback')}
                    />

                    <button
                        type="button"
                        onClick={again}
                        className="border-brand text-brand mt-4 min-h-[2.75rem] rounded-md border-2 px-6 py-3 font-semibold"
                    >
                        {t('public.demo.again')}
                    </button>
                </div>
            )}

            <Link
                href="/acheter"
                className="border-brand text-brand hover:bg-brand/5 mt-12 inline-block min-h-[2.75rem] rounded-md border-2 px-6 py-3 font-semibold"
            >
                {t('public.demo.cta')}
            </Link>
        </div>
    );
}
