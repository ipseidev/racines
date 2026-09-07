import { Head } from '@inertiajs/react';
import {
    lazy,
    Suspense,
    useCallback,
    useEffect,
    useMemo,
    useRef,
    useState,
} from 'react';

import AudioPlayer from '@/components/AudioPlayer';
import PhotoUploader from '@/components/PhotoUploader';
import { useT } from '@/hooks/useT';
import { createUploaderPorts } from '@/recorder/api';
import { reportClientEvent } from '@/recorder/clientEvents';
import {
    blobForSegment,
    clear,
    hasRoom,
    resumeInfo,
    type Draft,
} from '@/recorder/draftStore';
import {
    isRecordingSupported,
    kindOfMime,
    type RecordingKind,
} from '@/recorder/mime';
import { detectPlatform } from '@/recorder/platform';
import {
    initialSnapshot,
    reduce,
    type RecorderEvent,
    type RecorderSnapshot,
} from '@/recorder/recorderMachine';
import { uploadDraft } from '@/recorder/uploader';
import { useMediaRecorder } from '@/recorder/useMediaRecorder';
import { requestWakeLock } from '@/recorder/wakeLock';

import { formatDuration } from '@/recorder/duration';

import MicHelp from './MicHelp';

import ShareDecision from './ShareDecision';
import WrittenAnswer from './WrittenAnswer';

export type RecordLimits = {
    softWarningSeconds: number;
    hardStopSeconds: number;
    maxBytes: number;
    segmentMilliseconds: number;
    partSizeBytes: number;
    acceptedMimes: string[];
    /** Les bornes propres à la vidéo (T-210) : poids, débit, définition. */
    video: {
        maxBytes: number;
        bitsPerSecond: number;
        audioBitsPerSecond: number;
        height: number;
        acceptedMimes: string[];
    };
};

type Props = {
    firstName: string;
    addressForm: 'vous' | 'tu';
    question: string | null;
    storyRef: string;
    state: string;
    limits: RecordLimits;
    writtenAnswerMaxChars: number;
    /**
     * `immediate` : les trois choix arrivent ici, juste après la confirmation.
     * `deferred` : rien n'est demandé, la relecture viendra par message.
     */
    validationVariant: 'immediate' | 'deferred';
    shareDecisionAction: string;
    shareDecision: string | null;
    /** L'aisance avec un téléphone, déclarée à l'achat (TechComfort), ou rien. */
    techComfort: string | null;
};

/*
 * L'écran caméra et le lecteur vidéo sont chargés à la demande.
 *
 * Le budget de cette page est de 150 Ko gzip, et ce n'est pas une coquetterie :
 * elle s'ouvre en 4G sur de vieux téléphones, et « un budget dépassé, c'est
 * une histoire qui ne sera pas racontée ». Faire payer le poids de la caméra à
 * quelqu'un qui répond avec sa voix — c'est-à-dire au choix par défaut, donc à
 * la majorité — serait le mauvais arbitrage. Le morceau part chercher dès que
 * « En vous filmant » est touché, pendant que l'explication se lit : personne
 * ne voit d'attente.
 */
const VideoStage = lazy(() => import('./VideoStage'));
const VideoPlayer = lazy(() => import('@/components/VideoPlayer'));

/** Les niveaux d'aisance qui appellent plus d'aide à l'écran. */
const NEEDS_HELP = ['rarely', 'no_smartphone'];

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

function CameraIcon() {
    return (
        <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="1.8"
            strokeLinecap="round"
            strokeLinejoin="round"
            aria-hidden="true"
            className="record-icon"
        >
            <path d="M4 7h3l1.5-2h7L17 7h3a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V8a1 1 0 0 1 1-1Z" />
            <circle cx="12" cy="13" r="3.5" />
        </svg>
    );
}

function Check() {
    return (
        <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="2.5"
            aria-hidden="true"
            className="size-8"
        >
            <path d="m6 12 4 4 8-9" />
        </svg>
    );
}

/** L'onde d'une voix, décorative : elle dit « ça tourne » sans mesurer. */
function Wave() {
    return (
        <div
            className="flex h-8 items-center justify-center gap-[3px]"
            aria-hidden="true"
        >
            {Array.from({ length: 18 }, (_, i) => (
                <i
                    key={i}
                    className="wave-bar"
                    style={{ animationDelay: `${(i % 5) * -0.3}s` }}
                />
            ))}
        </div>
    );
}

/**
 * La page d'enregistrement, en six écrans.
 *
 * L'ordre n'est pas négociable : on explique, *puis* on demande le micro.
 * Une autorisation qui surgit sans prévenir se refuse par réflexe, et le
 * dossier fait du refus du micro par des seniors un risque identifié.
 *
 * Rien n'est jamais annoncé comme enregistré avant que le serveur l'ait
 * confirmé : `uploadDraft` ne rend `confirmed` que si le stockage a témoigné
 * détenir l'objet (doc 04 §11).
 *
 * Une seule chose par écran (T-138) : la question, puis un seul grand bouton.
 * Le halo qui respire pendant l'enregistrement est le seul mouvement, et il
 * s'arrête pour qui l'a demandé. Quand l'acheteur a dit que la personne est
 * peu à l'aise, l'aide vient avant la question, et l'écrit est un bouton.
 */
export default function Record({
    firstName,
    addressForm,
    question,
    storyRef,
    limits,
    writtenAnswerMaxChars,
    validationVariant,
    shareDecisionAction,
    shareDecision,
    techComfort,
}: Props) {
    const t = useT();
    const basePath = window.location.pathname;

    const [snapshot, setSnapshot] = useState<RecorderSnapshot>(initialSnapshot);
    const [draft, setDraft] = useState<Draft | null>(null);
    const [progress, setProgress] = useState(0);
    const [addingPhoto, setAddingPhoto] = useState(false);
    const [reviewUrl, setReviewUrl] = useState<string | null>(null);
    const [pausedUrl, setPausedUrl] = useState<string | null>(null);
    const [writing, setWriting] = useState(false);
    const [roomWarning, setRoomWarning] = useState(false);
    const [decided, setDecided] = useState<string | null>(shareDecision);
    // Se filmer n'est proposé que si le navigateur sait le faire : mieux vaut
    // pas de bouton qu'un bouton qui mène à un écran d'aide.
    const [videoSupported, setVideoSupported] = useState(false);

    const videoConstraints = useMemo(
        () => ({
            bitsPerSecond: limits.video.bitsPerSecond,
            audioBitsPerSecond: limits.video.audioBitsPerSecond,
            height: limits.video.height,
        }),
        [
            limits.video.audioBitsPerSecond,
            limits.video.bitsPerSecond,
            limits.video.height,
        ],
    );

    const recorder = useMediaRecorder(
        storyRef,
        limits.segmentMilliseconds,
        snapshot.context.kind,
        videoConstraints,
    );
    const startedAt = useRef<number | null>(null);
    const wakeLock = useRef<{ release: () => void } | null>(null);

    const send = useCallback(
        (event: RecorderEvent) => {
            setSnapshot((current) => reduce(current, event, limits));
        },
        [limits],
    );

    const tu = addressForm === 'tu';
    const needsHelp = NEEDS_HELP.includes(techComfort ?? '');
    const filming = snapshot.context.kind === 'video';

    /*
     * La nature de la relecture vient du brouillon, pas du choix courant :
     * un brouillon retrouvé après une purge d'onglet peut être une vidéo
     * alors que la machine repart, elle, sur son défaut.
     */
    const playbackKind: RecordingKind =
        draft === null ? snapshot.context.kind : kindOfMime(draft.mime);

    // Au chargement : brouillon retrouvé ? navigateur capable ?
    useEffect(() => {
        let cancelled = false;

        void (async () => {
            if (!isRecordingSupported()) {
                reportClientEvent('recorder_unsupported', {
                    platform: detectPlatform(),
                });
                send({ type: 'UNSUPPORTED' });

                return;
            }

            setVideoSupported(isRecordingSupported('video'));

            if (!(await hasRoom())) {
                setRoomWarning(true);
                reportClientEvent('storage_quota_low');
            }

            const info = await resumeInfo(storyRef);

            if (cancelled) {
                return;
            }

            if (info === null) {
                send({ type: 'BEGIN' });

                return;
            }

            setDraft(info.draft);
            send({ type: 'DRAFT_FOUND' });
        })();

        return () => {
            cancelled = true;
        };
    }, [send, storyRef]);

    // Le minuteur ne tourne que pendant l'enregistrement.
    useEffect(() => {
        if (snapshot.state !== 'recording') {
            return;
        }

        startedAt.current ??=
            Date.now() - snapshot.context.elapsedSeconds * 1000;

        const timer = window.setInterval(() => {
            const elapsed = Math.floor(
                (Date.now() - (startedAt.current ?? Date.now())) / 1000,
            );
            send({ type: 'TICK', seconds: elapsed });
        }, 1000);

        return () => window.clearInterval(timer);
    }, [send, snapshot.state, snapshot.context.elapsedSeconds]);

    // Une page cachée dont le recorder s'est arrêté : c'est une interruption.
    useEffect(() => {
        const onVisibilityChange = () => {
            if (document.visibilityState !== 'hidden') {
                return;
            }

            reportClientEvent('page_hidden', { state: snapshot.state });

            if (snapshot.state === 'recording' && recorder.isInactive()) {
                reportClientEvent('interrupted', {
                    segments: snapshot.context.segments,
                });
                startedAt.current = null;
                send({ type: 'INTERRUPTED' });
            }
        };

        document.addEventListener('visibilitychange', onVisibilityChange);

        return () =>
            document.removeEventListener(
                'visibilitychange',
                onVisibilityChange,
            );
    }, [recorder, send, snapshot.context.segments, snapshot.state]);

    useEffect(() => {
        if (snapshot.context.warningShown) {
            reportClientEvent('soft_warning_reached');
        }
    }, [snapshot.context.warningShown]);

    const askPermission = async () => {
        // `RETRY_PERMISSION` depuis l'aide, `READY` depuis l'explication : ce
        // sont deux entrées distinctes dans la demande de permission, et la
        // machine refuse l'une depuis l'état de l'autre. Envoyer `READY`
        // depuis `permission_denied` ne faisait donc rien — et le
        // `PERMISSION_GRANTED` qui suivait était ignoré à son tour, faute
        // d'être jamais entré en demande. L'aide restait à l'écran, pour
        // toujours, alors que le micro venait d'être autorisé (T-178).
        send({
            type:
                snapshot.state === 'permission_denied'
                    ? 'RETRY_PERMISSION'
                    : 'READY',
        });

        const granted = await recorder.requestPermission();

        if (!granted) {
            reportClientEvent(filming ? 'camera_denied' : 'mic_denied', {
                platform: detectPlatform(),
            });
            send({ type: 'PERMISSION_DENIED' });

            return;
        }

        reportClientEvent(filming ? 'camera_granted' : 'mic_granted');
        send({ type: 'PERMISSION_GRANTED' });
    };

    /**
     * Le choix de la forme, avant toute demande d'autorisation.
     *
     * La place restante est revérifiée ici, et pas seulement au chargement :
     * une vidéo pèse dix fois une voix, et le seuil qui convient à l'une ne
     * dit rien de l'autre.
     */
    const chooseMode = (kind: RecordingKind) => {
        reportClientEvent(kind === 'video' ? 'video_chosen' : 'audio_chosen');
        send({ type: 'CHOOSE_MODE', kind });

        if (kind !== 'video') {
            return;
        }

        // Le morceau de l'écran caméra part maintenant, pas à l'instant où
        // l'on en a besoin : l'explication qui suit couvre le trajet.
        void import('./VideoStage');

        void hasRoom(Math.round(limits.video.maxBytes / 2)).then((room) => {
            if (!room) {
                setRoomWarning(true);
                reportClientEvent('storage_quota_low', { kind });
            }
        });
    };

    const startRecording = async () => {
        await recorder.start();
        wakeLock.current = await requestWakeLock();
        startedAt.current = Date.now();
        reportClientEvent('recording_started');
        send({ type: 'RECORD' });
    };

    /*
     * Pause et reprise, nommées plutôt qu'écrites dans le `onClick` : depuis
     * T-212 elles servent deux mises en page — la colonne de texte et l'écran
     * caméra. Deux copies auraient fini par diverger, et c'est précisément
     * ici qu'un écart coûte cher (le compteur de T-140).
     */
    const pauseRecording = () => {
        recorder.pause();
        // Le point de départ du compteur s'oublie : à la reprise, il repart
        // de la durée acquise, pas de l'heure du premier « Commencer ».
        // Sinon la pause se comptait comme du temps parlé (T-140).
        startedAt.current = null;
        reportClientEvent('recording_paused');
        send({ type: 'PAUSE' });

        /*
         * La réécoute pendant la pause n'a de sens que pour une voix. Sur
         * l'écran caméra, poser un lecteur vidéo par-dessus l'image en train
         * de filmer donnerait deux visages à l'écran, dont un en différé :
         * on s'en passe, et « Terminer » mène à la relecture complète.
         */
        if (snapshot.context.kind !== 'video') {
            void preparePausedPlayback();
        }
    };

    const resumeRecording = () => {
        recorder.resume();
        reportClientEvent('recording_resumed');
        send({ type: 'RESUME' });
        clearPausedPlayback();
    };

    /*
     * Sortir de l'écran caméra sans rien perdre : la machine n'accepte
     * `CANCEL_MODE` que depuis `ready`, donc tant que rien n'a été dit. Le
     * flux est relâché au passage — une caméra qui reste allumée derrière un
     * écran qu'on vient de quitter est une caméra qu'on a oubliée.
     */
    const leaveCamera = () => {
        recorder.release();
        send({ type: 'CANCEL_MODE' });
    };

    const finish = async () => {
        clearPausedPlayback();
        send({ type: 'STOP' });
        await recorder.stop();
        wakeLock.current?.release();
        recorder.release();
        reportClientEvent('recording_stopped', {
            seconds: snapshot.context.elapsedSeconds,
        });

        const info = await resumeInfo(storyRef);
        setDraft(info?.draft ?? null);

        const blob = await blobForSegment(storyRef, 1);
        setReviewUrl(blob === null ? null : URL.createObjectURL(blob));

        send({ type: 'STOPPED' });
    };

    /*
     * Se réécouter pendant la pause (T-139) : le dernier morceau vient d'être
     * demandé au recorder, on lui laisse le temps d'arriver dans le brouillon,
     * puis on assemble ce qui a été dit jusqu'ici. Sur un navigateur qui ne
     * sait pas lire un enregistrement inachevé, le lecteur reste muet ; rien
     * n'est perdu, la réécoute complète vient après « Terminer ».
     */
    const preparePausedPlayback = async () => {
        await new Promise((resolve) => setTimeout(resolve, 600));
        const blob = await blobForSegment(
            storyRef,
            Math.max(1, snapshot.context.segments),
        );
        setPausedUrl(blob === null ? null : URL.createObjectURL(blob));
    };

    const clearPausedPlayback = () => {
        if (pausedUrl !== null) {
            URL.revokeObjectURL(pausedUrl);
        }

        setPausedUrl(null);
    };

    const upload = async () => {
        const current = draft ?? (await resumeInfo(storyRef))?.draft ?? null;

        if (current === null) {
            return;
        }

        send({ type: 'SEND' });
        reportClientEvent('upload_started', { segments: current.segments });

        try {
            const ports = createUploaderPorts(basePath);
            const outcome = await uploadDraft(
                current,
                {
                    ...ports,
                    onProgress: (sent, total) =>
                        setProgress(total === 0 ? 0 : sent / total),
                },
                snapshot.context.elapsedSeconds,
            );

            if (!outcome.confirmed) {
                reportClientEvent('upload_failed', { reason: 'not_confirmed' });
                send({ type: 'UPLOAD_FAILED' });

                return;
            }

            // Le brouillon ne s'efface qu'après confirmation du serveur.
            await clear(storyRef);
            send({ type: 'CONFIRMED' });
        } catch (error) {
            reportClientEvent('upload_failed', {
                reason:
                    error instanceof Error
                        ? error.message.slice(0, 120)
                        : 'unknown',
            });
            send({ type: 'UPLOAD_FAILED' });
        }
    };

    const greeting = useMemo(
        () =>
            t(tu ? 'narrator.record.greeting_tu' : 'narrator.record.greeting', {
                name: firstName,
            }),
        [firstName, t, tu],
    );

    const chooseWriting = () => {
        reportClientEvent('written_answer_chosen');
        setWriting(true);
    };

    if (writing) {
        return (
            <WrittenAnswer
                question={question}
                maxChars={writtenAnswerMaxChars}
                action={`${basePath}/written-answer`}
                onCancel={() => setWriting(false)}
            />
        );
    }

    if (
        snapshot.state === 'permission_denied' ||
        snapshot.state === 'unsupported'
    ) {
        return (
            <MicHelp
                platform={detectPlatform()}
                kind={snapshot.context.kind}
                canRetry={snapshot.state === 'permission_denied'}
                onRetry={() => void askPermission()}
                onWrite={chooseWriting}
            />
        );
    }

    const { state, context } = snapshot;
    const capturing = state === 'recording' || state === 'paused';

    /*
     * Se filmer prend tout l'écran (T-212), et remplace donc la page plutôt
     * que de s'y insérer : une image réduite à une vignette dans une colonne
     * de texte demande à quelqu'un de se cadrer dans un timbre-poste.
     *
     * Seulement à partir de `ready` : avant, il n'y a pas encore de flux, et
     * l'explication qui précède l'autorisation garde sa place dans la page.
     * Après « Terminer », la relecture reprend la mise en page ordinaire, où
     * vivent « Envoyer » et « Recommencer ».
     */
    if (filming && (state === 'ready' || capturing)) {
        return (
            <Suspense fallback={<div className="video-stage" />}>
                <VideoStage
                    stream={recorder.stream}
                    phase={
                        state === 'ready'
                            ? 'ready'
                            : (state as 'recording' | 'paused')
                    }
                    question={question}
                    elapsedSeconds={context.elapsedSeconds}
                    warningShown={context.warningShown}
                    onStart={() => void startRecording()}
                    onPause={pauseRecording}
                    onResume={resumeRecording}
                    onFinish={() => void finish()}
                    onExit={leaveCamera}
                />
            </Suspense>
        );
    }

    const primary =
        'btn-primary press record-action min-h-[2.75rem] w-full py-4 text-xl';
    const secondary =
        'btn-secondary press record-action min-h-[2.75rem] w-full py-4 text-xl';

    // La question reste sous les yeux tant qu'on répond ; après, elle laisse
    // la place à la réécoute, à l'envoi et au merci.
    const showQuestion =
        question !== null &&
        [
            'draft_found',
            'choosing_mode',
            'explaining',
            'requesting_permission',
            'ready',
            'recording',
            'paused',
            'interrupted',
        ].includes(state);

    return (
        <div className="flex flex-1 flex-col">
            <Head title={greeting} />

            {state !== 'confirmed' ? (
                <h1 className="font-display record-greeting leading-tight font-medium">
                    {greeting}
                </h1>
            ) : null}

            {showQuestion ? (
                <div className="card record-card mt-4 px-5 py-5">
                    <span
                        aria-hidden="true"
                        className="bg-brand-gold mb-3 block h-px w-10"
                    />
                    <p className="font-display text-brand record-question leading-snug font-medium">
                        {question}
                    </p>
                </div>
            ) : null}

            {roomWarning ? (
                <p role="status" className="text-brand-muted mt-5 text-base">
                    {t('narrator.record.storage_low')}
                </p>
            ) : null}

            {/* Un brouillon retrouvé ============================================ */}
            {state === 'draft_found' ? (
                <section className="panel enter mt-8 flex flex-col gap-4">
                    <h2 className="text-xl font-semibold">
                        {t('narrator.record.draft_title')}
                    </h2>
                    <p>{t('narrator.record.draft_body')}</p>

                    <button
                        type="button"
                        onClick={() => {
                            reportClientEvent('resumed_from_draft');
                            void (async () => {
                                const blob = await blobForSegment(storyRef, 1);
                                setReviewUrl(
                                    blob === null
                                        ? null
                                        : URL.createObjectURL(blob),
                                );
                                send({ type: 'RESUME_DRAFT' });
                            })();
                        }}
                        className={primary}
                    >
                        {t('narrator.record.draft_resume')}
                    </button>

                    <button
                        type="button"
                        onClick={() => {
                            reportClientEvent('draft_discarded');
                            void clear(storyRef).then(() =>
                                send({ type: 'DISCARD_DRAFT' }),
                            );
                        }}
                        className={secondary}
                    >
                        {t('narrator.record.draft_discard')}
                    </button>
                </section>
            ) : null}

            {/* Écran 0 : la voix, ou le visage (T-210) =========================== */}
            {state === 'choosing_mode' ? (
                <section className="enter mt-5 flex flex-1 flex-col justify-center gap-4">
                    <h2 className="font-display text-brand text-2xl leading-tight font-medium">
                        {t(
                            tu
                                ? 'narrator.record.mode_title_tu'
                                : 'narrator.record.mode_title',
                            { name: firstName },
                        )}
                    </h2>

                    <button
                        type="button"
                        onClick={() => chooseMode('audio')}
                        className={`${primary} flex items-center justify-center gap-3`}
                    >
                        <MicIcon />
                        {t('narrator.record.mode_audio')}
                    </button>

                    {videoSupported ? (
                        <button
                            type="button"
                            onClick={() => chooseMode('video')}
                            className={`${secondary} flex items-center justify-center gap-3`}
                        >
                            <CameraIcon />
                            {t('narrator.record.mode_video')}
                        </button>
                    ) : null}

                    <p className="text-brand-muted text-base">
                        {t(
                            tu
                                ? 'narrator.record.mode_help_tu'
                                : 'narrator.record.mode_help',
                        )}
                    </p>
                </section>
            ) : null}

            {/* Écran 1 : on explique, puis on demande ============================ */}
            {state === 'explaining' ? (
                <section className="enter mt-5 flex flex-1 flex-col justify-center gap-5">
                    <div className="panel flex flex-col gap-3">
                        <p>
                            {t(
                                filming
                                    ? tu
                                        ? 'narrator.record.camera_notice_tu'
                                        : 'narrator.record.camera_notice'
                                    : tu
                                      ? 'narrator.record.mic_notice_tu'
                                      : 'narrator.record.mic_notice',
                            )}
                        </p>
                        {needsHelp ? (
                            <p className="text-brand-muted text-base">
                                {t(`narrator.mic_help.${detectPlatform()}`)}
                            </p>
                        ) : null}
                    </div>

                    <button
                        type="button"
                        onClick={() => void askPermission()}
                        className={primary}
                    >
                        {t('narrator.record.ready')}
                    </button>
                </section>
            ) : null}

            {state === 'requesting_permission' ? (
                <p role="status" className="enter mt-8 flex items-center gap-3">
                    <span className="spinner text-brand" aria-hidden="true" />
                    {t('narrator.record.requesting')}
                </p>
            ) : null}

            {/* Écran 2 : le grand bouton rond ==================================== */}
            {state === 'ready' ? (
                <section className="enter flex flex-1 flex-col items-center justify-center gap-4 text-center">
                    <div className="record-halo">
                        <button
                            type="button"
                            onClick={() => void startRecording()}
                            className="bg-brand-accent text-brand-accent-foreground hover:bg-brand-accent-deep press record-dial flex flex-col items-center justify-center gap-2 rounded-full shadow-[0_18px_40px_rgba(176,67,42,0.35)] transition-colors"
                        >
                            {filming ? <CameraIcon /> : <MicIcon />}
                            <span className="record-label leading-none font-semibold">
                                {t('narrator.record.start')}
                            </span>
                        </button>
                    </div>
                    <p className="text-brand-muted max-w-xs text-base">
                        {t(
                            filming
                                ? tu
                                    ? 'narrator.record.tap_hint_video_tu'
                                    : 'narrator.record.tap_hint_video'
                                : tu
                                  ? 'narrator.record.tap_hint_tu'
                                  : 'narrator.record.tap_hint',
                        )}
                    </p>
                </section>
            ) : null}

            {/* Écran 3 : ça tourne =============================================== */}
            {capturing ? (
                <section className="enter record-stack flex flex-1 flex-col items-center justify-center gap-4 text-center">
                    <div
                        className={
                            state === 'recording' ? 'record-halo' : 'my-2'
                        }
                    >
                        <div
                            className={`flex flex-col items-center justify-center gap-1 rounded-full transition-[colors,width,height] duration-500 ${
                                state === 'recording'
                                    ? 'bg-brand-accent text-brand-accent-foreground record-dial'
                                    : 'bg-brand-linen text-brand record-dial-small'
                            }`}
                        >
                            <span className="record-time leading-none font-semibold tabular-nums">
                                {formatDuration(context.elapsedSeconds)}
                            </span>
                            <span className="sr-only">
                                {t('narrator.record.elapsed', {
                                    time: formatDuration(
                                        context.elapsedSeconds,
                                    ),
                                })}
                            </span>
                        </div>
                    </div>

                    <p
                        role="status"
                        className="flex items-center gap-2.5 text-lg font-medium"
                    >
                        <span
                            aria-hidden="true"
                            className={`size-3 flex-none rounded-full ${
                                state === 'recording'
                                    ? 'bg-brand-accent'
                                    : 'bg-brand-sand'
                            }`}
                        />
                        {state === 'recording'
                            ? t('narrator.record.recording')
                            : t('narrator.record.paused')}
                    </p>

                    {state === 'recording' ? <Wave /> : null}

                    {state === 'paused' && pausedUrl !== null ? (
                        <div className="enter w-full">
                            {playbackKind === 'video' ? (
                                <Suspense fallback={null}>
                                    <VideoPlayer src={pausedUrl} />
                                </Suspense>
                            ) : (
                                <AudioPlayer src={pausedUrl} compact />
                            )}
                        </div>
                    ) : null}

                    {context.warningShown ? (
                        <p className="text-brand-muted text-base">
                            {t('narrator.record.soft_warning')}
                        </p>
                    ) : null}

                    <div className="mt-auto flex w-full flex-col gap-3">
                        <button
                            type="button"
                            onClick={
                                state === 'recording'
                                    ? pauseRecording
                                    : resumeRecording
                            }
                            className={secondary}
                        >
                            {state === 'recording'
                                ? t('narrator.record.pause')
                                : t('narrator.record.resume')}
                        </button>

                        <button
                            type="button"
                            onClick={() => void finish()}
                            className={primary}
                        >
                            {t('narrator.record.finish')}
                        </button>
                    </div>
                </section>
            ) : null}

            {state === 'interrupted' ? (
                <section className="panel enter mt-8 flex flex-col gap-4">
                    <p role="status">{t('narrator.record.interrupted')}</p>

                    <button
                        type="button"
                        onClick={() => {
                            void recorder.startNewSegment().then(() => {
                                send({ type: 'RESUME_AFTER_INTERRUPTION' });
                            });
                        }}
                        className={primary}
                    >
                        {t('narrator.record.interrupted_resume')}
                    </button>

                    <button
                        type="button"
                        onClick={() => void finish()}
                        className={secondary}
                    >
                        {t('narrator.record.finish')}
                    </button>
                </section>
            ) : null}

            {state === 'stopping' ? (
                <p role="status" className="enter mt-8 flex items-center gap-3">
                    <span className="spinner text-brand" aria-hidden="true" />
                    {context.hardStopReached
                        ? t('narrator.record.hard_stop')
                        : t('narrator.record.uploading')}
                </p>
            ) : null}

            {/* Écran 4 : se réécouter, puis envoyer ============================== */}
            {state === 'reviewing' ? (
                <section className="enter mt-4 flex flex-1 flex-col justify-center gap-5">
                    <div>
                        <h2 className="font-display text-brand text-2xl leading-tight font-medium">
                            {t(
                                playbackKind === 'video'
                                    ? 'narrator.record.review_title_video'
                                    : 'narrator.record.review_title',
                            )}
                        </h2>
                        <p className="text-brand-muted mt-2 text-base">
                            {t('narrator.record.review_body')}
                        </p>
                    </div>

                    {reviewUrl === null ? null : playbackKind === 'video' ? (
                        <Suspense fallback={null}>
                            <VideoPlayer src={reviewUrl} />
                        </Suspense>
                    ) : (
                        <AudioPlayer src={reviewUrl} />
                    )}

                    <button
                        type="button"
                        onClick={() => void upload()}
                        className={primary}
                    >
                        {t('narrator.record.send')}
                    </button>

                    <button
                        type="button"
                        onClick={() => {
                            if (
                                window.confirm(
                                    t('narrator.record.restart_confirm'),
                                )
                            ) {
                                void clear(storyRef).then(() =>
                                    send({ type: 'RESTART' }),
                                );
                            }
                        }}
                        className={secondary}
                    >
                        {t('narrator.record.restart')}
                    </button>
                </section>
            ) : null}

            {/* Écran 5 : l'envoi ================================================= */}
            {state === 'uploading' ? (
                <section className="enter mt-8 flex flex-col gap-4">
                    <p
                        role="status"
                        className="flex items-center gap-3 text-lg font-medium"
                    >
                        <span
                            className="spinner text-brand"
                            aria-hidden="true"
                        />
                        {t('narrator.record.uploading')}
                    </p>

                    <div
                        role="progressbar"
                        aria-valuemin={0}
                        aria-valuemax={100}
                        aria-valuenow={Math.round(progress * 100)}
                        aria-label={t('narrator.record.uploading')}
                        className="progress-bar"
                    >
                        <span style={{ width: `${progress * 100}%` }} />
                    </div>

                    <p className="text-brand-muted text-base">
                        {t('narrator.record.uploading_notice')}
                    </p>
                </section>
            ) : null}

            {state === 'upload_failed' ? (
                <section className="panel enter mt-8 flex flex-col gap-4">
                    <h2 className="text-xl font-semibold">
                        {t('narrator.record.upload_failed_title')}
                    </h2>
                    <p>{t('narrator.record.upload_failed_body')}</p>

                    <button
                        type="button"
                        onClick={() => {
                            reportClientEvent('upload_retried');
                            send({ type: 'RETRY_UPLOAD' });
                            void upload();
                        }}
                        className={primary}
                    >
                        {t('narrator.record.retry')}
                    </button>
                </section>
            ) : null}

            {/* Écran 6 : c'est enregistré ======================================== */}
            {state === 'confirmed' ? (
                <section className="enter mt-2">
                    <div className="flex flex-col items-center text-center">
                        <span
                            aria-hidden="true"
                            className="bg-brand text-brand-foreground animate-pop-in flex size-12 items-center justify-center rounded-full"
                        >
                            <Check />
                        </span>
                        <h1
                            role="status"
                            className="font-display text-brand mt-3 text-[1.5rem] leading-tight font-medium"
                        >
                            {t('narrator.record.confirmed_title')}
                        </h1>
                        <p className="mt-1.5 text-lg">
                            {t('narrator.record.confirmed_body', {
                                name: firstName,
                            })}
                        </p>
                        {validationVariant === 'immediate' ? null : (
                            <p className="text-brand-muted mt-2 text-base">
                                {t('narrator.record.confirmed_next')}
                            </p>
                        )}
                    </div>

                    {/*
                     * Variante A : la question se pose maintenant, pendant que
                     * le narrateur est encore là. C'est tout l'objet du test
                     * de Phase 0A : la validation comme récompense d'un tap.
                     */}
                    {validationVariant === 'immediate' ? (
                        decided === null ? (
                            <ShareDecision
                                action={shareDecisionAction}
                                onDecided={setDecided}
                            />
                        ) : (
                            <p role="status" className="panel enter mt-8">
                                {t(
                                    `narrator.share_decision.recorded.${decided}`,
                                )}
                            </p>
                        )
                    ) : null}

                    {/*
                     * L'ajout d'une photo, **après** la confirmation et
                     * jamais avant : l'enregistrement est ce qui compte, et
                     * proposer une photo au milieu ferait abandonner le
                     * récit à mi-chemin. Facultatif de bout en bout.
                     */}
                    {addingPhoto ? (
                        <PhotoUploader
                            action={`${basePath}/photos`}
                            onDone={() => setAddingPhoto(false)}
                        />
                    ) : (
                        <button
                            type="button"
                            onClick={() => setAddingPhoto(true)}
                            className="text-brand-muted hover:text-brand record-optional mt-4 min-h-[2.75rem] w-full text-base underline underline-offset-4"
                        >
                            {t('common.photos.add')}
                        </button>
                    )}
                </section>
            ) : null}

            {/* L'écrit, toujours possible ======================================== */}
            {state === 'choosing_mode' ||
            state === 'explaining' ||
            state === 'ready' ? (
                <button
                    type="button"
                    onClick={chooseWriting}
                    className={
                        needsHelp
                            ? `${secondary} mt-4`
                            : 'text-brand-muted hover:text-brand mt-4 min-h-[2.75rem] w-full text-base underline underline-offset-4'
                    }
                >
                    {t('narrator.record.written_link')}
                </button>
            ) : null}
        </div>
    );
}
