import { useState } from 'react';

import AudioPlayer from '@/components/AudioPlayer';
import { LevelBars } from '@/components/LevelBars';
import { useT } from '@/hooks/useT';
import { formatDuration } from '@/recorder/duration';
import { useLocalRecorder } from '@/recorder/useLocalRecorder';

type Props = {
    firstName: string;
    tu: boolean;
    seconds: number;
    segmentMilliseconds: number;
    /** Passer à la vraie question. `played` dit si le tour a été joué. */
    onDone: (played: boolean) => void;
    /** Le micro vient d'être demandé : la mesure commence là. */
    onStart: () => void;
};

function MicIcon() {
    return (
        <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="1.8"
            strokeLinecap="round"
            aria-hidden="true"
            className="record-icon"
        >
            <rect x="9" y="3" width="6" height="11" rx="3" />
            <path d="M5 11a7 7 0 0 0 14 0M12 18v3M9 21h6" />
        </svg>
    );
}

/**
 * Le tour de chauffe du tout premier lien (T-247).
 *
 * Quinze secondes qui ne partent nulle part, proposées **avant** la question,
 * une seule fois dans la vie d'une narratrice. Ce qu'il règle n'est pas
 * l'ignorance mais la peur : entendre sa propre voix revenir lève la question
 * que personne ne pose à voix haute — « est-ce que je vais savoir faire ? ».
 * Et il règle au passage la permission du micro, qui est le premier point de
 * chute du parcours, à un moment où se tromper ne coûte rien.
 *
 * **C'est une répétition, pas une explication.** Le même gros bouton rond, le
 * même halo, le même vu-mètre, le même compteur que l'écran suivant : ce qui
 * s'apprend ici se retrouve là-bas à la même place. Un tutoriel qui montre
 * autre chose que l'écran réel apprend un écran qui n'existe pas.
 *
 * Trois refus assumés :
 *
 *  - **rien n'est envoyé, rien n'est gardé** — `useLocalRecorder` ne touche ni
 *    le réseau ni IndexedDB, et la page le dit au lieu de le taire ;
 *  - **on peut passer**, en un geste aussi visible que celui d'essayer : une
 *    personne pressée ne doit pas avoir à traverser un tutoriel pour répondre ;
 *  - **un micro refusé ne piège pas** : on le dit, et on l'emmène à sa
 *    question, où vit l'aide propre à son téléphone.
 */
export default function FirstRun({
    firstName,
    tu,
    seconds: maxSeconds,
    segmentMilliseconds,
    onDone,
    onStart,
}: Props) {
    const t = useT();
    const { phase, seconds, levels, playbackUrl, start, stop, again } =
        useLocalRecorder({ maxSeconds, segmentMilliseconds });

    /*
     * Le dernier écran, et il n'est pas décoratif : quelqu'un qui vient
     * d'entendre sa voix revenir peut croire qu'il a répondu. On le dit donc
     * en toutes lettres — l'essai n'a rien laissé, la question arrive — avant
     * de l'y emmener. C'est le seul risque que ce tour de chauffe ajoute, et
     * une phrase suffit à le retirer.
     */
    const [over, setOver] = useState(false);

    const primary =
        'btn-primary press record-action min-h-[2.75rem] w-full py-4 text-xl';
    const secondary =
        'btn-secondary press record-action min-h-[2.75rem] w-full py-4 text-xl';

    /*
     * Un micro absent ou refusé ne retient personne : le tour de chauffe est
     * un confort, la question est le service. L'aide propre au téléphone vit
     * sur l'écran suivant, et s'y affichera au bon moment.
     */
    if (phase === 'unsupported' || phase === 'refused') {
        return (
            <section className="enter flex flex-1 flex-col justify-center gap-5 text-center">
                <h1 className="font-display text-brand text-2xl leading-tight font-medium">
                    {t('narrator.first_run.refused_title')}
                </h1>
                <p className="text-brand-muted text-lg leading-snug">
                    {t('narrator.first_run.refused_body')}
                </p>
                <button
                    type="button"
                    onClick={() => onDone(false)}
                    className={primary}
                >
                    {t('narrator.first_run.refused_button')}
                </button>
            </section>
        );
    }

    if (over) {
        return (
            <section className="enter flex flex-1 flex-col justify-center gap-5 text-center">
                <h1 className="font-display text-brand text-[1.75rem] leading-tight font-medium">
                    {t('narrator.first_run.over_title')}
                </h1>
                <p className="text-lg leading-snug">
                    {t(
                        tu
                            ? 'narrator.first_run.over_body_tu'
                            : 'narrator.first_run.over_body',
                    )}
                </p>
                <button
                    type="button"
                    onClick={() => onDone(true)}
                    className={primary}
                >
                    {t('narrator.first_run.over_button')}
                </button>
            </section>
        );
    }

    return (
        <section className="enter flex flex-1 flex-col justify-center gap-6 text-center">
            {phase === 'idle' ? (
                <>
                    <h1 className="font-display text-brand text-[1.75rem] leading-tight font-medium">
                        {t('narrator.first_run.title', { name: firstName })}
                    </h1>

                    {/* La promesse d'abord, le bouton ensuite : c'est elle qui
                        autorise à appuyer. */}
                    <p className="panel text-lg leading-snug">
                        {t('narrator.first_run.why')}
                    </p>

                    <div className="record-halo mx-auto">
                        <button
                            type="button"
                            onClick={() => {
                                onStart();
                                void start();
                            }}
                            className="bg-brand-accent text-brand-accent-foreground hover:bg-brand-accent-deep press record-dial flex flex-col items-center justify-center gap-2 rounded-full shadow-[0_18px_40px_rgba(176,67,42,0.35)] transition-colors"
                        >
                            <MicIcon />
                            <span className="record-label leading-none font-semibold">
                                {t('narrator.first_run.start')}
                            </span>
                        </button>
                    </div>

                    <button
                        type="button"
                        onClick={() => onDone(false)}
                        className="text-brand-muted hover:text-brand min-h-[2.75rem] text-base underline underline-offset-4"
                    >
                        {t('narrator.first_run.skip')}
                    </button>
                </>
            ) : null}

            {phase === 'recording' ? (
                <>
                    <div className="record-halo mx-auto">
                        <div className="bg-brand-accent text-brand-accent-foreground record-dial flex flex-col items-center justify-center rounded-full">
                            <span className="record-time leading-none font-semibold tabular-nums">
                                {formatDuration(maxSeconds - seconds)}
                            </span>
                        </div>
                    </div>

                    <p
                        role="status"
                        className="flex items-center justify-center gap-2.5 text-lg font-medium"
                    >
                        <span
                            aria-hidden="true"
                            className="bg-brand-accent size-3 flex-none rounded-full"
                        />
                        {t(
                            tu
                                ? 'narrator.first_run.recording_tu'
                                : 'narrator.first_run.recording',
                        )}
                    </p>

                    <LevelBars levels={levels} />

                    <button type="button" onClick={stop} className={secondary}>
                        {t('narrator.first_run.stop')}
                    </button>
                </>
            ) : null}

            {phase === 'ready' && playbackUrl !== null ? (
                <>
                    <h1 className="font-display text-brand text-2xl leading-tight font-medium">
                        {t(
                            tu
                                ? 'narrator.first_run.listen_title_tu'
                                : 'narrator.first_run.listen_title',
                        )}
                    </h1>

                    <AudioPlayer src={playbackUrl} />

                    <p className="text-brand-muted text-base leading-snug">
                        {t(
                            tu
                                ? 'narrator.first_run.listen_body_tu'
                                : 'narrator.first_run.listen_body',
                        )}
                    </p>

                    <button
                        type="button"
                        onClick={() => setOver(true)}
                        className={primary}
                    >
                        {t('narrator.first_run.done')}
                    </button>

                    <button type="button" onClick={again} className={secondary}>
                        {t('narrator.first_run.again')}
                    </button>
                </>
            ) : null}
        </section>
    );
}
