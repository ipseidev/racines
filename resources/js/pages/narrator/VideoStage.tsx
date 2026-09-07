import { Head } from '@inertiajs/react';
import { useEffect, useRef } from 'react';
import { createPortal } from 'react-dom';

import { useT } from '@/hooks/useT';
import { formatDuration } from '@/recorder/duration';

export type VideoPhase = 'ready' | 'recording' | 'paused';

type Props = {
    stream: MediaStream | null;
    phase: VideoPhase;
    question: string | null;
    elapsedSeconds: number;
    warningShown: boolean;
    onStart: () => void;
    onPause: () => void;
    onResume: () => void;
    onFinish: () => void;
    onExit: () => void;
};

/**
 * L'écran caméra, en plein écran (T-212).
 *
 * Se filmer ne se conduit pas comme s'enregistrer. Une voix n'a rien à
 * montrer, et la page garde alors sa colonne étroite, sa question dans une
 * carte et son gros bouton au milieu. Une image, elle, **est** l'écran : la
 * réduire à une vignette dans une colonne de texte, c'est demander à
 * quelqu'un de se cadrer dans un timbre-poste. On prend donc tout l'écran, et
 * le texte passe par-dessus.
 *
 * Trois règles gouvernent ce fichier :
 *
 *  - **La question s'efface quand ça tourne.** Elle est là pour être lue
 *    avant de parler ; pendant, elle recouvre le visage qu'on est en train de
 *    cadrer. C'est aussi ce que demande le dossier en interdisant tout ce qui
 *    ressemble à un compte à rebours anxiogène : moins il reste à l'écran,
 *    mieux on raconte.
 *  - **On sort sans rien perdre.** Le bouton de sortie n'existe que **tant que
 *    rien n'a été dit** ; dès que l'enregistrement tourne, la seule porte est
 *    « Terminer », qui garde ce qui a été raconté. La machine refuse le reste
 *    (`CANCEL_MODE` n'est accepté que depuis `ready`).
 *  - **Le texte reste lisible sur n'importe quelle image.** Une vidéo est un
 *    fond imprévisible : du blanc sur une fenêtre ensoleillée ne se lit pas.
 *    D'où les voiles sombres, qui ne sont pas une décoration mais la
 *    condition du contraste — « en transparence » ne veut pas dire illisible.
 *
 * Rendu par un portail plutôt qu'en place : la mise en page de la narratrice
 * anime son contenu à l'entrée, et un `transform`, même terminé, est le genre
 * de détail qui transforme un `position: fixed` en `position: absolute` sans
 * prévenir. Le portail met l'écran hors de portée de cette question.
 */
export default function VideoStage({
    stream,
    phase,
    question,
    elapsedSeconds,
    warningShown,
    onStart,
    onPause,
    onResume,
    onFinish,
    onExit,
}: Props) {
    const t = useT();
    const video = useRef<HTMLVideoElement>(null);

    useEffect(() => {
        const element = video.current;

        if (element === null) {
            return;
        }

        element.srcObject = stream;

        return () => {
            element.srcObject = null;
        };
    }, [stream]);

    // La page dessous ne défile plus : sur iOS, un glissement sur l'image
    // ferait rebondir le document derrière l'écran caméra.
    useEffect(() => {
        const previous = document.body.style.overflow;
        document.body.style.overflow = 'hidden';

        return () => {
            document.body.style.overflow = previous;
        };
    }, []);

    const capturing = phase !== 'ready';

    return createPortal(
        <div className="video-stage">
            <Head title={t('narrator.record.video_stage')} />

            {/*
             * `muted` n'est pas un détail de confort : sans lui, le téléphone
             * se réécoute lui-même et siffle. Retourné comme un miroir, parce
             * que c'est ce que tout le monde attend d'une caméra frontale —
             * l'image enregistrée, elle, ne l'est pas.
             */}
            <video
                ref={video}
                autoPlay
                muted
                playsInline
                aria-label={t('common.video.self_view')}
                className="video-stage-feed"
            />

            <div className="video-stage-layer">
                <div className="video-stage-top">
                    {capturing ? (
                        <p role="status" className="video-stage-timer">
                            <span
                                aria-hidden="true"
                                className={
                                    phase === 'recording'
                                        ? 'video-stage-dot'
                                        : 'video-stage-dot video-stage-dot-paused'
                                }
                            />
                            <span className="tabular-nums">
                                {formatDuration(elapsedSeconds)}
                            </span>
                            <span className="sr-only">
                                {phase === 'recording'
                                    ? t('narrator.record.recording')
                                    : t('narrator.record.paused')}
                                {' — '}
                                {t('narrator.record.elapsed', {
                                    time: formatDuration(elapsedSeconds),
                                })}
                            </span>
                        </p>
                    ) : (
                        <>
                            <button
                                type="button"
                                onClick={onExit}
                                className="video-stage-exit press"
                            >
                                <svg
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    strokeWidth="2"
                                    strokeLinecap="round"
                                    aria-hidden="true"
                                    className="size-5"
                                >
                                    <path d="M6 6l12 12M18 6L6 18" />
                                </svg>
                                {t('narrator.record.mode_exit')}
                            </button>

                            {question === null ? null : (
                                <p className="video-stage-question">
                                    {question}
                                </p>
                            )}
                        </>
                    )}

                    {warningShown && capturing ? (
                        <p className="video-stage-notice">
                            {t('narrator.record.soft_warning')}
                        </p>
                    ) : null}
                </div>

                <div className="video-stage-bottom">
                    {capturing ? (
                        <>
                            <button
                                type="button"
                                onClick={
                                    phase === 'recording' ? onPause : onResume
                                }
                                className="video-stage-secondary press"
                            >
                                {phase === 'recording'
                                    ? t('narrator.record.pause')
                                    : t('narrator.record.resume')}
                            </button>

                            <button
                                type="button"
                                onClick={onFinish}
                                className="video-stage-primary press"
                            >
                                {t('narrator.record.finish')}
                            </button>
                        </>
                    ) : (
                        <button
                            type="button"
                            onClick={onStart}
                            className="video-stage-shutter press"
                        >
                            <span
                                aria-hidden="true"
                                className="video-stage-shutter-dot"
                            />
                            <span className="video-stage-shutter-label">
                                {t('narrator.record.start')}
                            </span>
                        </button>
                    )}
                </div>
            </div>
        </div>,
        document.body,
    );
}
