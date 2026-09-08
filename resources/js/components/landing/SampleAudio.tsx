import { useEffect, useRef, useState } from 'react';

import { track } from '@/components/landing/track';
import Wave from '@/components/Wave';
import { useT } from '@/hooks/useT';
import { formatDuration } from '@/lib/format';

type Sample = { src: string; disclosed: boolean };

function PlayIcon({ playing }: { playing: boolean }) {
    return (
        <svg
            viewBox="0 0 24 24"
            fill="currentColor"
            aria-hidden="true"
            className="size-6"
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
 * Le lecteur d'exemple de la variante — **l'unique instance de la page**.
 *
 * Les autres sections ne dupliquent pas de lecteur : elles pointent vers
 * l'ancre `#ecouter`. Deux lecteurs sur la même page, c'est deux extraits qui
 * peuvent jouer ensemble et deux fois les mêmes éléments focusables.
 *
 * Ce n'est pas `AudioPlayer` : celui-ci écoute une histoire entière — saut de
 * quinze secondes, vitesse, curseur saisissable, mesure de ce qui est écouté —
 * et il affiche « Il reste 0:00 » tant que les entêtes ne sont pas là. Sur une
 * page de vente, une durée de zéro sous un bouton « Écouter » se lit comme un
 * extrait vide. Ici : un bouton, une frise, la durée **seulement** quand elle
 * est connue, et la transcription qui se déplie.
 *
 * Sans fichier, le composant n'affiche aucun bouton : l'extrait est présenté
 * comme un texte, ce qu'il est alors. Un bouton de lecture au-dessus d'un
 * fichier absent est pire que pas de bouton.
 */
export default function SampleAudio({
    sample,
    variant,
}: {
    sample: Sample | null;
    variant: string;
}) {
    const t = useT();
    const audio = useRef<HTMLAudioElement>(null);
    const started = useRef(false);
    const [playing, setPlaying] = useState(false);
    const [position, setPosition] = useState(0);
    const [duration, setDuration] = useState(0);
    const [transcript, setTranscript] = useState(false);

    useEffect(() => {
        const element = audio.current;

        if (element === null) {
            return;
        }

        // L'état suit l'élément et non le clic : une lecture qui s'arrête
        // toute seule — fin de l'extrait, appel entrant, autre son qui prend
        // la main — doit rendre son bouton « Écouter l'extrait ».
        const onLoaded = () => setDuration(element.duration);

        // Les entêtes peuvent être arrivées avant que l'effet ne s'abonne :
        // fichier en cache, retour en arrière dans l'historique.
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

    const label = t('public.landing.hero.card.transcript_label');
    const words = t('public.landing.hero.card.transcript');

    if (sample === null) {
        return (
            <div className="flex flex-col gap-3">
                <p className="text-brand text-[1.0625rem] font-semibold">
                    {t('public.lp.offer.player.read_instead')}
                </p>
                <p className="text-brand-muted font-display text-[1.05rem] leading-relaxed italic">
                    {words}
                </p>
            </div>
        );
    }

    const toggle = () => {
        const element = audio.current;

        if (element === null) {
            return;
        }

        if (element.paused) {
            if (!started.current) {
                started.current = true;
                track('lp_sample_play', { variant, section: 'offer' });
            }

            void element.play();
        } else {
            element.pause();
        }
    };

    return (
        <div className="flex flex-col gap-3">
            {/* eslint-disable-next-line jsx-a11y/media-has-caption -- la transcription se déplie juste dessous */}
            <audio ref={audio} src={sample.src} preload="metadata" />

            <div className="flex items-center gap-3">
                <button
                    type="button"
                    onClick={toggle}
                    aria-pressed={playing}
                    className="bg-brand text-brand-foreground press hover:bg-brand-deep flex min-h-[2.75rem] items-center gap-2.5 rounded-full pr-5 pl-3.5 text-[1rem] font-semibold transition-colors"
                >
                    <PlayIcon playing={playing} />
                    {playing
                        ? t('common.player.pause')
                        : t('common.player.play')}
                </button>

                <Wave
                    bars={22}
                    playing={playing}
                    progress={duration > 0 ? position / duration : 0}
                    className="min-w-0 flex-1 overflow-hidden"
                />

                {/*
                 * La durée **totale**, et rien tant qu'elle n'est pas connue.
                 * Le temps écoulé a été essayé : une seconde après le clic, la
                 * carte affichait « 0 s » sous un bouton « Mettre en pause »,
                 * ce qui se lit comme un extrait vide. L'avancement est déjà
                 * dit par la frise ; le chiffre, lui, doit répondre à « ça dure
                 * combien de temps ».
                 */}
                {duration > 0 && (
                    <span className="text-brand-muted flex-none text-[0.95rem] tabular-nums">
                        {formatDuration(duration)}
                    </span>
                )}
            </div>

            {sample.disclosed && (
                <p className="text-brand-muted text-[0.85rem] leading-snug">
                    {t('public.landing.hero.card.synthetic')}
                </p>
            )}

            <div>
                <button
                    type="button"
                    onClick={() => setTranscript(!transcript)}
                    aria-expanded={transcript}
                    aria-controls="lp-transcript"
                    className="text-brand min-h-[2.75rem] text-[1rem] font-semibold underline decoration-2 underline-offset-4"
                >
                    {transcript
                        ? t('public.lp.offer.player.transcript_hide')
                        : t('public.lp.offer.player.transcript_show')}
                </button>

                {/*
                 * Toujours dans le document, masqué par `hidden` : WCAG 2.2 AA
                 * (1.2.1) demande un équivalent à tout média sonore, et un
                 * lecteur d'écran doit pouvoir l'atteindre. La transcription
                 * suit l'audio au mot près, hésitations comprises.
                 */}
                <div
                    id="lp-transcript"
                    hidden={!transcript}
                    className="border-brand-sand mt-3 border-l-2 pl-4"
                >
                    <p className="text-brand-muted text-[0.8rem] font-semibold tracking-[0.08em] uppercase">
                        {label}
                    </p>
                    <p className="text-brand-muted mt-2 text-[1rem] leading-relaxed">
                        {words}
                    </p>
                </div>
            </div>
        </div>
    );
}
