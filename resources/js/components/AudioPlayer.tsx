import { useCallback, useEffect, useRef, useState } from 'react';

import { useT } from '@/hooks/useT';

type Props = {
    src: string;
    /** Appelé avec les secondes écoutées depuis le dernier envoi. */
    onProgress?: (seconds: number) => void;
    /** Cadence d'envoi, en secondes. Dix par défaut (bloc 08 §6.3). */
    reportEverySeconds?: number;
};

function formatTime(seconds: number): string {
    if (!Number.isFinite(seconds) || seconds < 0) {
        return '0:00';
    }

    const minutes = Math.floor(seconds / 60);
    const rest = Math.floor(seconds % 60);

    return `${minutes}:${String(rest).padStart(2, '0')}`;
}

/**
 * Le lecteur audio de l'espace famille.
 *
 * Un `<audio>` natif, mais **jamais** ses contrôles par défaut : ils font
 * 20 px de haut sur un téléphone, et le public de ce produit est celui qui a
 * le plus de mal à les toucher. D'où des boutons larges, avec un libellé
 * texte à côté de l'icône — un pictogramme seul se devine, il ne se lit pas.
 *
 * Le ×0,9 n'est pas un gadget : une voix âgée qui articule mal se comprend
 * beaucoup mieux un peu ralentie, et c'est exactement le corpus de ce
 * produit.
 *
 * Ce qui est rapporté au serveur, ce sont les secondes **jouées**, mesurées
 * par le temps de lecture réel : déplacer le curseur ne compte pas pour de
 * l'écoute.
 */
export default function AudioPlayer({
    src,
    onProgress,
    reportEverySeconds = 10,
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

    return (
        <section aria-label={t('family.player.progress')}>
            {/* eslint-disable-next-line jsx-a11y/media-has-caption -- la transcription de l'histoire est affichée juste dessous */}
            <audio ref={audio} src={src} preload="metadata" />

            <div className="flex flex-wrap items-center gap-3">
                <button
                    type="button"
                    onClick={toggle}
                    aria-pressed={playing}
                    className="bg-brand text-brand-foreground min-h-[3.5rem] min-w-[3.5rem] rounded-md px-6 py-3 text-lg font-medium"
                >
                    {playing
                        ? t('family.player.pause')
                        : t('family.player.play')}
                </button>

                <button
                    type="button"
                    onClick={() => seek(-15)}
                    className="border-brand-sand min-h-[2.75rem] rounded-md border px-4 py-2 text-base"
                >
                    {t('family.player.back15')}
                </button>

                <button
                    type="button"
                    onClick={() => seek(15)}
                    className="border-brand-sand min-h-[2.75rem] rounded-md border px-4 py-2 text-base"
                >
                    {t('family.player.forward15')}
                </button>

                <button
                    type="button"
                    onClick={() => setSpeed(slower ? 1 : 0.9)}
                    aria-pressed={slower}
                    className="border-brand-sand min-h-[2.75rem] rounded-md border px-4 py-2 text-base"
                >
                    {slower
                        ? t('family.player.normal')
                        : t('family.player.slower')}
                </button>
            </div>

            <label htmlFor="audio-progress" className="sr-only">
                {t('family.player.progress')}
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
                className="mt-4 h-6 w-full"
            />

            <p aria-live="polite" className="text-brand-muted mt-2 text-base">
                {t('family.player.remaining', { time: formatTime(remaining) })}
            </p>
        </section>
    );
}
