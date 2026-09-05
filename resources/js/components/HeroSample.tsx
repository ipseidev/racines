import { useEffect, useRef, useState } from 'react';

import Wave from '@/components/Wave';
import { useT } from '@/hooks/useT';
import { formatDuration } from '@/lib/format';

type Sample = {
    src: string;
    /** La carte porte-t-elle la mention sous le bouton. */
    disclosed: boolean;
};

type Props = {
    /** L'extrait, ou `null` quand la page est servie sans audio. */
    sample: Sample | null;
};

function PlayIcon({ playing }: { playing: boolean }) {
    return (
        <svg
            viewBox="0 0 24 24"
            fill="currentColor"
            aria-hidden="true"
            className="size-5"
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
 * L'extrait écoutable de la carte du héros (T-149).
 *
 * La page promet « sa voix se réécoute à chaque page » dès le premier écran ;
 * jusqu'ici la carte l'affirmait avec une frise animée qui ne jouait rien.
 * Elle le prouve maintenant.
 *
 * Ce n'est **pas** `AudioPlayer` : celui-ci écoute une histoire entière — gros
 * bouton, curseur saisissable, sauts, vitesse — et il mesure ce qui est
 * écouté. Posé dans une carte de 380 px à cheval sur la photo du héros
 * (T-144), il doublerait la hauteur de la carte et couvrirait la photo sur un
 * téléphone. Ici il faut un bouton, la frise déjà présente, et rien d'autre.
 *
 * Sans extrait, la carte reprend exactement ce qu'elle montrait avant : la
 * frise décorative et sa légende. Une page déployée avant l'audio ne montre
 * jamais un bouton qui ne joue rien.
 */
export default function HeroSample({ sample }: Props) {
    const t = useT();
    const audio = useRef<HTMLAudioElement>(null);
    const [playing, setPlaying] = useState(false);
    const [position, setPosition] = useState(0);
    const [duration, setDuration] = useState(0);

    useEffect(() => {
        const element = audio.current;

        if (element === null) {
            return;
        }

        // L'état suit l'élément et non le clic : une lecture qui s'arrête
        // toute seule — fin de l'extrait, appel entrant, autre son qui prend
        // la main — doit rendre son bouton « Écouter ».
        const onLoaded = () => setDuration(element.duration);

        // Les entêtes peuvent être arrivées avant que l'effet ne s'abonne —
        // fichier en cache, retour en arrière dans l'historique. Sans cette
        // relecture, la durée resterait invisible jusqu'au premier clic.
        if (element.readyState >= HTMLMediaElement.HAVE_METADATA) {
            onLoaded();
        }

        const onTime = () => setPosition(element.currentTime);
        const onPlay = () => setPlaying(true);
        const onPause = () => setPlaying(false);

        element.addEventListener('loadedmetadata', onLoaded);
        element.addEventListener('timeupdate', onTime);
        element.addEventListener('play', onPlay);
        element.addEventListener('pause', onPause);
        element.addEventListener('ended', onPause);

        return () => {
            element.removeEventListener('loadedmetadata', onLoaded);
            element.removeEventListener('timeupdate', onTime);
            element.removeEventListener('play', onPlay);
            element.removeEventListener('pause', onPause);
            element.removeEventListener('ended', onPause);
        };
    }, [sample]);

    if (sample === null) {
        return (
            <div className="text-brand-muted flex items-center gap-3.5 text-[0.9rem]">
                <Wave />
                <span>
                    <b className="text-brand font-semibold">
                        {t('public.landing.hero.card.answers')}
                    </b>{' '}
                    {t('public.landing.hero.card.duration')}
                </span>
            </div>
        );
    }

    const toggle = () => {
        const element = audio.current;

        if (element === null) {
            return;
        }

        if (element.paused) {
            void element.play();
        } else {
            element.pause();
        }
    };

    return (
        <div className="flex flex-col gap-2.5">
            {/* eslint-disable-next-line jsx-a11y/media-has-caption -- la transcription suit, en lecture d'écran */}
            <audio ref={audio} src={sample.src} preload="metadata" />

            <div className="flex items-center gap-3.5">
                <button
                    type="button"
                    onClick={toggle}
                    aria-pressed={playing}
                    aria-label={
                        playing
                            ? t('common.player.pause')
                            : t('common.player.play')
                    }
                    className="bg-brand text-brand-foreground press hover:bg-brand-deep flex size-11 min-h-[2.75rem] flex-none items-center justify-center rounded-full transition-colors"
                >
                    <PlayIcon playing={playing} />
                </button>

                {/* Assez de barres pour remplir la carte sur un écran de
                    bureau ; sur un téléphone les dernières sont rognées, et
                    une frise qui continue hors du cadre ne choque pas. */}
                <Wave
                    bars={30}
                    playing={playing}
                    progress={duration > 0 ? position / duration : 0}
                    className="min-w-0 flex-1 overflow-hidden"
                />

                {/* Rien tant que la durée n'est pas connue : « 0 s » sous un
                    bouton « Écouter » se lit comme un extrait vide. */}
                {duration > 0 ? (
                    <span className="text-brand-muted flex-none text-[0.9rem] tabular-nums">
                        {formatDuration(position > 0 ? position : duration)}
                    </span>
                ) : null}
            </div>

            {sample.disclosed ? (
                <p className="text-brand-muted text-[0.8rem] leading-snug">
                    {t('public.landing.hero.card.synthetic')}
                </p>
            ) : null}

            {/* L'équivalent textuel qu'exige WCAG 2.2 AA (1.2.1) : la carte
                n'a pas la place de l'afficher, le lecteur d'écran le lit. */}
            <div className="sr-only">
                <p>{t('public.landing.hero.card.transcript_label')}</p>
                <p>{t('public.landing.hero.card.transcript')}</p>
            </div>
        </div>
    );
}
