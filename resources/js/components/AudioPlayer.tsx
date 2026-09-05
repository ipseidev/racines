import { useCallback, useEffect, useRef, useState } from 'react';

import { useT } from '@/hooks/useT';

type Props = {
    src: string;
    onProgress?: (seconds: number) => void;
    reportEverySeconds?: number;
    /** Sans les pastilles de saut et de vitesse : le bouton et la frise. */
    compact?: boolean;
};

function formatTime(seconds: number): string {
    if (!Number.isFinite(seconds) || seconds < 0) {
        return '0:00';
    }

    const minutes = Math.floor(seconds / 60);
    const rest = Math.floor(seconds % 60);

    return `${minutes}:${String(rest).padStart(2, '0')}`;
}

function PlayIcon({ playing }: { playing: boolean }) {
    return (
        <svg
            viewBox="0 0 24 24"
            fill="currentColor"
            aria-hidden="true"
            className="size-7"
        >
            {playing ? (
                <>
                    <rect x="6" y="5" width="4" height="14" rx="1" />
                    <rect x="14" y="5" width="4" height="14" rx="1" />
                </>
            ) : (
                <path d="M8 5.5v13a1 1 0 0 0 1.53.85l10-6.5a1 1 0 0 0 0-1.7l-10-6.5A1 1 0 0 0 8 5.5Z" />
            )}
        </svg>
    );
}

/**
 * Le lecteur d'une histoire, le même pour la narratrice qui se réécoute et
 * pour la famille qui écoute (T-138).
 *
 * Un seul grand bouton, une frise qu'on peut saisir, le temps écoulé et ce
 * qui reste, deux sauts de quinze secondes et la vitesse en pastille. Pas de
 * boutons de tailles différentes : les réglages sont des pastilles, l'écoute
 * est le bouton.
 *
 * Il mesure ce qui est **écouté**, pas ce qui est sauté : un bond du curseur
 * ne compte pas, et ce qui reste à déclarer part avec la page.
 */
export default function AudioPlayer({
    src,
    onProgress,
    reportEverySeconds = 10,
    compact = false,
}: Props) {
    const t = useT();
    const audio = useRef<HTMLAudioElement>(null);
    const [playing, setPlaying] = useState(false);
    const [position, setPosition] = useState(0);
    const [duration, setDuration] = useState(0);
    const [slower, setSlower] = useState(false);

    // Secondes jouées depuis le dernier envoi, et position à la dernière
    // mesure : la différence entre les deux distingue une lecture d'un saut.
    const unreported = useRef(0);
    const lastPosition = useRef(0);

    const report = useCallback(() => {
        const seconds = Math.floor(unreported.current);

        if (seconds >= 1) {
            unreported.current -= seconds;
            onProgress?.(seconds);
        }
    }, [onProgress]);

    useEffect(() => {
        const element = audio.current;

        if (element === null) {
            return;
        }

        const onTimeUpdate = () => {
            const now = element.currentTime;
            const advanced = now - lastPosition.current;

            // Une avance plausible est une lecture ; un bond est un saut du
            // curseur, et ne compte pas comme de l'écoute.
            if (advanced > 0 && advanced < 2) {
                unreported.current += advanced;
            }

            lastPosition.current = now;
            setPosition(now);

            if (unreported.current >= reportEverySeconds) {
                report();
            }
        };

        const onLoaded = () => setDuration(element.duration);

        // Les entêtes peuvent être arrivées avant que l'effet ne s'abonne —
        // fichier déjà en cache, retour en arrière dans l'historique. Sans
        // cette relecture, le lecteur annonce « Il reste 0:00 » sur une
        // histoire de trois minutes, jusqu'au premier clic.
        if (element.readyState >= HTMLMediaElement.HAVE_METADATA) {
            onLoaded();
        }

        const onEnded = () => {
            setPlaying(false);
            report();
        };

        element.addEventListener('timeupdate', onTimeUpdate);
        element.addEventListener('loadedmetadata', onLoaded);
        element.addEventListener('ended', onEnded);

        return () => {
            element.removeEventListener('timeupdate', onTimeUpdate);
            element.removeEventListener('loadedmetadata', onLoaded);
            element.removeEventListener('ended', onEnded);
        };
    }, [report, reportEverySeconds]);

    // Ce qui reste à écouter part avec la page : un onglet fermé à la
    // vingt-neuvième seconde ne doit pas perdre la mesure.
    useEffect(() => {
        const flush = () => report();
        window.addEventListener('pagehide', flush);

        return () => {
            window.removeEventListener('pagehide', flush);
            flush();
        };
    }, [report]);

    const toggle = () => {
        const element = audio.current;

        if (element === null) {
            return;
        }

        if (element.paused) {
            lastPosition.current = element.currentTime;
            void element.play();
            setPlaying(true);
        } else {
            element.pause();
            setPlaying(false);
            report();
        }
    };

    const seek = (delta: number) => {
        const element = audio.current;

        if (element === null) {
            return;
        }

        element.currentTime = Math.max(
            0,
            Math.min(element.duration || 0, element.currentTime + delta),
        );
        lastPosition.current = element.currentTime;
    };

    const setSpeed = (value: number) => {
        const element = audio.current;

        if (element !== null) {
            element.playbackRate = value;
        }

        setSlower(value !== 1);
    };

    const remaining = Math.max(0, duration - position);
    const percent = duration > 0 ? (position / duration) * 100 : 0;

    const pill =
        'chip press min-h-[2.75rem] cursor-pointer text-[0.95rem] before:hidden hover:border-brand';

    return (
        <section
            aria-label={t('common.player.progress')}
            className={`card ${compact ? 'p-3' : 'p-5'}`}
        >
            {/* eslint-disable-next-line jsx-a11y/media-has-caption -- la transcription de l'histoire est affichée juste dessous */}
            <audio ref={audio} src={src} preload="metadata" />

            <div className="flex items-center gap-4">
                <button
                    type="button"
                    onClick={toggle}
                    aria-pressed={playing}
                    aria-label={
                        playing
                            ? t('common.player.pause')
                            : t('common.player.play')
                    }
                    className="bg-brand text-brand-foreground press hover:bg-brand-deep flex size-16 min-h-[3.5rem] flex-none items-center justify-center rounded-full shadow-[0_8px_20px_rgba(47,74,63,0.25)] transition-colors"
                >
                    <PlayIcon playing={playing} />
                </button>

                <div className="min-w-0 flex-1">
                    <label htmlFor="audio-progress" className="sr-only">
                        {t('common.player.progress')}
                    </label>
                    <input
                        id="audio-progress"
                        type="range"
                        min={0}
                        max={Math.max(1, Math.floor(duration))}
                        value={Math.floor(position)}
                        onChange={(event) => {
                            const element = audio.current;
                            const next = Number(event.target.value);

                            if (element !== null) {
                                element.currentTime = next;
                                lastPosition.current = next;
                            }

                            setPosition(next);
                        }}
                        className="audio-range w-full"
                        style={{
                            background: `linear-gradient(to right, var(--color-brand) ${percent}%, var(--color-brand-sand) ${percent}%)`,
                        }}
                    />
                    <div className="text-brand-muted mt-1.5 flex justify-between text-[0.95rem] tabular-nums">
                        <span>
                            {t('common.player.elapsed', {
                                time: formatTime(position),
                            })}
                        </span>
                        <span aria-live="polite">
                            {t('common.player.remaining', {
                                time: formatTime(remaining),
                            })}
                        </span>
                    </div>
                </div>
            </div>

            {compact ? null : (
                <div className="mt-4 flex flex-wrap gap-2">
                    <button
                        type="button"
                        onClick={() => seek(-15)}
                        className={pill}
                        aria-label={t('common.player.back15')}
                    >
                        −15 s
                    </button>
                    <button
                        type="button"
                        onClick={() => seek(15)}
                        className={pill}
                        aria-label={t('common.player.forward15')}
                    >
                        +15 s
                    </button>
                    <button
                        type="button"
                        onClick={() => setSpeed(slower ? 1 : 0.9)}
                        aria-pressed={slower}
                        className={`${pill} ${slower ? 'border-brand bg-brand/5' : ''}`}
                    >
                        {slower
                            ? t('common.player.normal')
                            : t('common.player.slower')}
                    </button>
                </div>
            )}
        </section>
    );
}
