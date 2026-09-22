import { Head, router } from '@inertiajs/react';
import {
    lazy,
    Suspense,
    useCallback,
    useEffect,
    useMemo,
    useRef,
    useState,
} from 'react';

import { useFormat } from '@/hooks/useFormat';
import { useT } from '@/hooks/useT';
import { stagger } from '@/lib/motion';
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
import type { QuestionPhoto } from './QuestionPhotos';
import { uploadDraft } from '@/recorder/uploader';
import { useMediaRecorder } from '@/recorder/useMediaRecorder';
import { requestWakeLock } from '@/recorder/wakeLock';

import { formatDuration } from '@/recorder/duration';

export type RecordLimits = {
    softWarningSeconds: number;
    hardStopSeconds: number;
    maxBytes: number;
    segmentMilliseconds: number;
    partSizeBytes: number;
    /** L'arrêt ferme du tour de chauffe du premier lien (T-247). */
    firstRunSeconds: number;
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
    /** Les photos qui **posent** la question, jointes par la famille. */
    questionPhotos: QuestionPhoto[];
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
    /** Cette personne n'a jamais rien enregistré : on lui propose un tour de chauffe. */
    firstTime: boolean;
    /** Elle a déclaré d'avance que ses histoires partent dès qu'elles sont prêtes (D-10). */
    declaredSharing: boolean;
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
// Le tour de chauffe ne pèse que pour celles qui le voient : une seule fois
// dans une vie de narratrice, et jamais pour les autres.
const FirstRun = lazy(() => import('./FirstRun'));

/*
 * Rien de tout cela ne s'affiche à l'ouverture de la page, et tout s'y
 * chargeait : la réécoute, le dépôt de photo, l'aide du micro, la réponse
 * écrite et les trois choix de partage pesaient six kilo-octets payés par
 * quelqu'un qui n'a encore rien fait. Sur une 4G de campagne, six kilo-octets
 * sont une seconde — et le budget de cette page (conventions §4) n'existe que
 * parce qu'une page qui n'arrive pas est une histoire qui ne sera pas
 * racontée. Chacun arrive maintenant à l'écran qui en a besoin.
 */
const AudioPlayer = lazy(() => import('@/components/AudioPlayer'));
const PhotoUploader = lazy(() => import('@/components/PhotoUploader'));
const MicHelp = lazy(() => import('./MicHelp'));
// Les photos qui posent la question : chargées seulement quand il y en a,
// donc jamais pour la majorité des questions (T-251, budget conventions §4).
const QuestionPhotos = lazy(() => import('./QuestionPhotos'));
const WrittenAnswer = lazy(() => import('./WrittenAnswer'));
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
 * Deux mouvements, pas un de plus, et ils ne se croisent jamais — la carte de
 * la question respire tant qu'on la lit, le halo respire pendant que ça
 * tourne. Tous deux s'arrêtent pour qui l'a demandé. Quand l'acheteur a dit
 * que la personne est peu à l'aise, l'aide vient avant la question, et
 * l'écrit est un bouton.
 */
export default function Record({
    firstName,
    addressForm,
    question,
    questionPhotos,
    storyRef,
    limits,
    writtenAnswerMaxChars,
    validationVariant,
    shareDecisionAction,
    shareDecision,
    techComfort,
    firstTime,
    declaredSharing,
}: Props) {
    const t = useT();
    const fmt = useFormat();
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
    // Le tour de chauffe s'efface dès qu'il est joué ou passé : il ne revient
    // pas au rechargement, même si rien n'est encore enregistré.
    const [rehearsing, setRehearsing] = useState(firstTime);

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

        /*
         * La voix enchaîne, la caméra non (T-248).
         *
         * Pour la voix, l'autorisation était suivie d'un écran qui redemandait
         * le même geste : le grand bouton venait d'être pressé, et il fallait
         * presser le grand bouton. On enregistre donc tout de suite — la
         * pastille du navigateur, le halo qui respire et le compteur disent
         * que ça tourne, et la pause comme le recommencement restent à une
         * portée de doigt.
         *
         * Se filmer garde son temps : « on se voit avant de commencer » est
         * la règle de T-210, et l'aperçu de soi est précisément ce que cet
         * écran-là existe pour montrer.
         */
        if (!filming) {
            await startRecording();
        }
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
            <Suspense fallback={null}>
                <WrittenAnswer
                    question={question}
                    maxChars={writtenAnswerMaxChars}
                    action={`${basePath}/written-answer`}
                    onCancel={() => setWriting(false)}
                />
            </Suspense>
        );
    }

    if (
        snapshot.state === 'permission_denied' ||
        snapshot.state === 'unsupported'
    ) {
        return (
            <Suspense fallback={null}>
                <MicHelp
                    platform={detectPlatform()}
                    kind={snapshot.context.kind}
                    canRetry={snapshot.state === 'permission_denied'}
                    onRetry={() => void askPermission()}
                    onWrite={chooseWriting}
                />
            </Suspense>
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

    /*
     * Le tour de chauffe passe **avant** tout le reste, et remplace la page :
     * un essai posé sous la question n'aurait été qu'une explication de plus
     * à côté d'elle. Il ne s'affiche qu'au tout premier lien, et seulement
     * là où rien n'a commencé — un brouillon retrouvé veut dire qu'elle a
     * déjà appuyé, et on ne propose pas une répétition à qui est en scène.
     */
    if (rehearsing && state === 'choosing_mode') {
        return (
            <div className="flex flex-1 flex-col">
                <Head title={greeting} />

                <Suspense fallback={null}>
                    <FirstRun
                        firstName={firstName}
                        tu={tu}
                        seconds={limits.firstRunSeconds}
                        segmentMilliseconds={limits.segmentMilliseconds}
                        onDone={(played) => {
                            reportClientEvent(
                                played ? 'first_run_done' : 'first_run_skipped',
                            );
                            setRehearsing(false);
                        }}
                        onStart={() => reportClientEvent('first_run_started')}
                    />
                </Suspense>
            </div>
        );
    }

    /*
     * Les libellés reviennent au corps du système (1,0625 rem). À 1,25 rem,
     * deux dalles pleine largeur pesaient plus lourd que la question : on
     * lisait le bouton avant elle. La cible du doigt ne bouge pas — pleine
     * largeur, `py-4`, 2,75 rem de haut au minimum — et sous 740 px de
     * hauteur d'écran, `record-action` imposait déjà 1,125 rem.
     */
    const primary =
        'btn-primary press record-action min-h-[2.75rem] w-full py-4';
    const secondary =
        'btn-secondary press record-action min-h-[2.75rem] w-full py-4';

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

    /*
     * Là où l'écran a de la place, la question la prend (21 septembre 2026).
     *
     * Sur l'écran de choix, les gestes sont ancrés en bas et la question
     * l'était en haut : deux cent quinze pixels de vide restaient entre les
     * deux, un quart d'un téléphone de 844 px, et ce vide n'appartenait à
     * personne. La question se lisait comme un bandeau d'en-tête plutôt que
     * comme le sujet de la page. Elle absorbe maintenant cet espace et s'y
     * centre : le vide l'encadre au lieu de l'isoler, et les boutons restent
     * au bas de l'écran, où le pouce les trouve.
     *
     * Les écrans suivants — l'explication, le grand bouton, le cadran qui
     * tourne — ont déjà leur propre bloc souple et tiennent tout juste dans
     * la hauteur (T-139) : là, rien ne bouge.
     */
    const questionTakesTheRoom = ['draft_found', 'choosing_mode'].includes(
        state,
    );

    return (
        <div className="flex flex-1 flex-col">
            <Head title={greeting} />

            {/*
             * La salutation oriente, elle ne s'annonce pas. Elle a été le plus
             * grand caractère de la page jusqu'au 20 septembre 2026, au-dessus
             * d'une question plus petite qu'elle : on lisait d'abord ce qu'on
             * savait déjà. Elle passe en ligne discrète, et la question prend
             * la place.
             *
             * Elle voyage avec la question depuis le 21 septembre : centrée
             * en même temps qu'elle, elle reste la ligne qui l'introduit. Une
             * salutation restée collée en haut pendant que la question
             * descend au milieu, ce sont deux objets sans rapport.
             */}
            {state !== 'confirmed' ? (
                <div
                    className={
                        questionTakesTheRoom
                            ? 'flex flex-1 flex-col justify-center'
                            : undefined
                    }
                >
                    <h1 className="record-greeting text-brand-muted leading-snug">
                        {greeting}
                    </h1>

                    {/*
                     * La question dans sa carte, qui est la seule surface
                     * élevée de l'écran et la seule chose qui bouge.
                     *
                     * La carte avait été retirée le matin du 21 septembre :
                     * posée à même le lin, la question a gagné en taille et
                     * perdu en présence, faute de séparation figure/fond. Ce
                     * qui la met en avant n'est donc ni sa taille seule ni le
                     * vide autour d'elle, c'est d'être le seul objet en
                     * relief de la page — et de respirer (`question-card`).
                     */}
                    {showQuestion ? (
                        <div
                            className={[
                                'question-card mt-4',
                                questionTakesTheRoom
                                    ? ''
                                    : 'question-card-compact',
                                // Elle cesse de bouger dès que la personne
                                // parle : le halo est alors le seul mouvement.
                                capturing ? 'question-card-still' : '',
                                // …et dès qu'elle porte une vignette à
                                // toucher : une cible ne dérive pas sous le
                                // doigt. Le filet d'or, lui, continue.
                                questionPhotos.length > 0
                                    ? 'question-card-steady'
                                    : '',
                            ].join(' ')}
                        >
                            <span
                                aria-hidden="true"
                                className="question-rule"
                            />

                            {/*
                             * Les photos qui posent la question.
                             *
                             * Elles viennent **avant** le texte parce que
                             * c'est l'ordre dans lequel on parle : on tend la
                             * photo, puis on demande. Une seule occupe la
                             * largeur de la carte ; plusieurs se rangent en
                             * bande qui défile de côté — jamais en grille,
                             * qui les rendrait toutes minuscules sur un
                             * téléphone.
                             *
                             * La hauteur est bornée en `vh` et non en pixels :
                             * la page d'enregistrement doit tenir dans
                             * l'écran sans défilement (T-139), et une photo
                             * est la seule chose ici dont la taille ne vient
                             * pas de nous. Les dimensions sont écrites dans
                             * le style pour que rien ne saute quand l'image
                             * arrive — sur une 4G, elle arrive après le
                             * texte.
                             */}
                            {/*
                             * La photo ne vit que sur l'écran qui pose la
                             * question.
                             *
                             * Mesuré sur la fenêtre réelle d'un iPhone : les
                             * écrans qui suivent — l'explication, le grand
                             * bouton, le cadran qui tourne — sont déjà à
                             * **zéro pixel** de marge (726 px de contenu pour
                             * 726 px de fenêtre). Une vignette de 88 px y
                             * fait donc déborder de 120 px, c'est-à-dire
                             * « Terminer » sous le bord pendant qu'on
                             * raconte. Il n'y a pas d'arbitrage à faire : la
                             * photo a servi, elle s'efface, et la question
                             * écrite reste sous les yeux jusqu'au bout.
                             */}
                            {questionTakesTheRoom &&
                            questionPhotos.length > 0 ? (
                                <Suspense fallback={null}>
                                    <QuestionPhotos photos={questionPhotos} />
                                </Suspense>
                            ) : null}

                            <p className="font-display text-brand record-question question-text leading-tight font-medium text-balance">
                                {question}
                            </p>
                        </div>
                    ) : null}
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
                /*
                 * Les gestes descendent au bas de l'écran (`mt-auto`) et le
                 * vide passe au-dessus d'eux, entre la question et eux. Ils
                 * occupaient le centre optique — là où tombe le premier
                 * regard sur un téléphone — pendant que la question, ancrée
                 * en haut, se lisait après. Le pouce y gagne aussi.
                 */
                <section
                    className={`enter mt-auto flex flex-col gap-3 ${
                        // Une question illustrée a déjà sa hauteur : les
                        // quarante pixels qui séparaient la carte des gestes
                        // n'ont plus rien à séparer, et la page doit tenir
                        // dans l'écran (T-139).
                        questionPhotos.length > 0 ? 'pt-3' : 'pt-10'
                    }`}
                >
                    {/*
                     * La consigne ne s'affiche plus : elle redisait ce que les
                     * libellés disent déjà, dans l'encre la plus noire de la
                     * page et juste au-dessus du bouton — c'est elle qui
                     * amarrait le regard en bas. Elle reste pour les lecteurs
                     * d'écran, qui n'ont pas la mise en page pour comprendre
                     * ce qu'on leur demande.
                     */}
                    <h2 className="sr-only">
                        {t(
                            tu
                                ? 'narrator.record.mode_title_tu'
                                : 'narrator.record.mode_title',
                            { name: firstName },
                        )}
                    </h2>

                    {/*
                     * Deux réponses à la même question, donc deux objets de la
                     * même famille (`mode-tile`) : même forme, même hauteur,
                     * même icône à la même place. Se filmer avait été réduit à
                     * un lien souligné pour alléger l'écran — mais la réponse
                     * à « deux dalles trop lourdes » n'était pas d'en effacer
                     * une, c'était d'alléger les deux. Aucune n'est en
                     * terracotta : cet écran aiguille, il n'agit pas, et la
                     * couleur d'action attend le grand bouton rond du suivant.
                     */}
                    <button
                        type="button"
                        onClick={() => chooseMode('audio')}
                        className="mode-tile mode-tile-primary press"
                    >
                        <MicIcon />
                        {t('narrator.record.mode_audio')}
                    </button>

                    {videoSupported ? (
                        <button
                            type="button"
                            onClick={() => chooseMode('video')}
                            className="mode-tile mode-tile-secondary press"
                        >
                            <CameraIcon />
                            {t('narrator.record.mode_video')}
                        </button>
                    ) : null}

                    {/*
                     * L'explication est la légende des deux tuiles, pas un
                     * bloc de plus : elle se serre contre elles (`-mt-1`) au
                     * lieu de flotter entre elles et le lien de l'écrit.
                     */}
                    {/*
                     * L'explication du choix s'efface quand la question porte
                     * une photo : trois lignes de gris de plus sur un écran
                     * qui en a déjà beaucoup, et c'est la place qui manque
                     * pour montrer l'image en grand (T-139). Elle reste pour
                     * les lecteurs d'écran, qui n'ont pas l'image pour
                     * comprendre la différence entre les deux boutons.
                     */}
                    <p
                        className={`text-brand-muted -mt-1 text-base ${
                            questionPhotos.length > 0 ? 'sr-only' : ''
                        }`}
                    >
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
                <section className="enter mt-4 flex flex-1 flex-col items-center justify-center gap-5 text-center">
                    {/*
                     * La consigne du micro n'est plus un panneau sable.
                     *
                     * Depuis que la question a retrouvé sa carte, l'écran
                     * portait deux surfaces pleines au même niveau : la
                     * question en blanc, la consigne en sable. Deux surfaces
                     * qui ne hiérarchisent rien ne font qu'un écran encombré.
                     * L'écran a maintenant trois niveaux et trois
                     * traitements : la carte en relief pour le sujet, du
                     * texte nu pour la consigne, la couleur d'action pour le
                     * geste — et rien d'autre en terracotta de tout le
                     * parcours.
                     */}
                    <div className="flex max-w-sm flex-col gap-3">
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

                    {/*
                     * Le grand bouton rond est **ici**, et non un écran plus
                     * loin (T-248). Il y avait entre le choix et le geste un
                     * écran « Je suis prêt·e » qui ne faisait que confirmer
                     * une intention déjà exprimée : trois gestes délibérés
                     * pour commencer à parler, là où deux suffisent. Le
                     * bouton est le même, au même endroit, avec la même
                     * étiquette que sur l'écran suivant — ce qui s'apprend
                     * une fois se retrouve à sa place.
                     *
                     * L'explication reste **au-dessus** de lui : c'est elle
                     * qui doit précéder la demande d'autorisation (T-210,
                     * doc 04 §9), et elle la précède toujours.
                     */}
                    <div className="record-halo mx-auto">
                        <button
                            type="button"
                            onClick={() => void askPermission()}
                            className="bg-brand-accent text-brand-accent-foreground hover:bg-brand-accent-deep press record-dial flex flex-col items-center justify-center gap-2 rounded-full shadow-[0_18px_40px_rgba(176,67,42,0.35)] transition-colors"
                        >
                            {filming ? <CameraIcon /> : <MicIcon />}
                            <span className="record-label leading-none font-semibold">
                                {t(
                                    filming
                                        ? 'narrator.record.open_camera'
                                        : 'narrator.record.start',
                                )}
                            </span>
                        </button>
                    </div>
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
                                <Suspense fallback={null}>
                                    <AudioPlayer src={pausedUrl} compact />
                                </Suspense>
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
                        <Suspense fallback={null}>
                            <AudioPlayer src={reviewUrl} />
                        </Suspense>
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
                    {/*
                     * Le seul écran du parcours où l'on félicite quelqu'un.
                     *
                     * Il tenait en deux lignes suivies d'un formulaire, et
                     * une personne qui vient de raconter un morceau de sa vie
                     * recevait un accusé de réception. Il dit maintenant ce
                     * qu'elle vient de faire — **une durée**, concrète là où
                     * « c'est enregistré » est administratif — sous le même
                     * filet d'or que l'écran de bienvenue : ce sont les deux
                     * moments du produit qui se ressemblent.
                     */}
                    <div className="flex flex-col items-center py-4 text-center">
                        <span
                            aria-hidden="true"
                            className="bg-brand text-brand-foreground animate-pop-in flex size-16 items-center justify-center rounded-full"
                        >
                            <Check />
                        </span>

                        <h1
                            role="status"
                            className="font-display text-brand enter mt-6 text-[1.875rem] leading-tight font-medium text-balance"
                            style={stagger(1)}
                        >
                            {t('narrator.record.confirmed_title')}
                        </h1>

                        <p
                            className="text-brand-muted enter mt-2 text-lg"
                            style={stagger(2)}
                        >
                            {t('narrator.record.confirmed_body', {
                                name: firstName,
                            })}
                        </p>

                        <span
                            aria-hidden="true"
                            className="rule-gold mt-7"
                            style={stagger(3)}
                        />

                        {context.elapsedSeconds > 0 ? (
                            <>
                                <p
                                    className="text-brand-muted enter mt-7 text-base"
                                    style={stagger(4)}
                                >
                                    {t('narrator.record.confirmed_duration')}
                                </p>
                                <p
                                    className="font-display text-brand enter mt-1 text-[1.625rem] leading-snug font-medium"
                                    style={stagger(5)}
                                >
                                    {fmt.duration(context.elapsedSeconds)}
                                </p>
                            </>
                        ) : null}

                        {validationVariant === 'immediate' ? null : (
                            <p
                                className="enter mt-7 max-w-[30ch] text-[1.0625rem] leading-snug"
                                style={stagger(6)}
                            >
                                {t('narrator.record.confirmed_next')}
                            </p>
                        )}
                    </div>

                    {/*
                     * On **annonce** le partage, on ne le demande plus (T-250).
                     *
                     * « Que souhaitez-vous faire de cette histoire ? » se
                     * posait ici en variante A, et c'était l'objet du test de
                     * Phase 0A. Le partage permanent est devenu le sixième
                     * accord du « J'accepte » : plus aucun choix à faire, ni
                     * à l'acceptation ni après chaque récit, parce que chaque
                     * tap est une occasion d'abandonner et qu'on s'adresse à
                     * des gens que la technique intimide.
                     *
                     * La sortie reste ouverte pour ce récit-là, et c'est elle
                     * qui rachète la granularité perdue : un accord permanent
                     * n'est pas un engagement histoire par histoire, et
                     * « garder celle-ci pour moi » est à un doigt.
                     *
                     * Un projet sans déclaration ne voit rien du tout : la
                     * relecture lui sera demandée par message, `ApplyShare-
                     * Decision` s'en charge. Il n'en existe plus depuis que
                     * l'accord est donné à l'acceptation ; seuls d'anciens
                     * projets peuvent passer par là.
                     */}
                    {declaredSharing ? (
                        <div className="border-brand-sand mt-8 flex flex-col items-center gap-3 border-t pt-7 text-center">
                            {decided === null ? (
                                <>
                                    <p
                                        role="status"
                                        className="text-[1.0625rem]"
                                    >
                                        {t('narrator.record.shared_by_default')}
                                    </p>
                                    <button
                                        type="button"
                                        onClick={() =>
                                            router.post(
                                                shareDecisionAction,
                                                { decision: 'keep_private' },
                                                {
                                                    preserveScroll: true,
                                                    onSuccess: () =>
                                                        setDecided(
                                                            'keep_private',
                                                        ),
                                                },
                                            )
                                        }
                                        className="text-brand-muted hover:text-brand min-h-[2.75rem] text-base underline underline-offset-4"
                                    >
                                        {t('narrator.record.keep_this_one')}
                                    </button>
                                </>
                            ) : (
                                <p role="status" className="text-[1.0625rem]">
                                    {t(
                                        `narrator.share_decision.recorded.${decided}`,
                                    )}
                                </p>
                            )}
                        </div>
                    ) : null}

                    {/*
                     * L'ajout d'une photo, **après** la confirmation et
                     * jamais avant : l'enregistrement est ce qui compte, et
                     * proposer une photo au milieu ferait abandonner le
                     * récit à mi-chemin. Facultatif de bout en bout.
                     */}
                    {addingPhoto ? (
                        <Suspense fallback={null}>
                            <PhotoUploader
                                action={`${basePath}/photos`}
                                onDone={() => setAddingPhoto(false)}
                            />
                        </Suspense>
                    ) : (
                        <button
                            type="button"
                            onClick={() => setAddingPhoto(true)}
                            className="text-brand-muted hover:text-brand record-optional mt-4 min-h-[2.75rem] w-full text-base underline underline-offset-4"
                        >
                            {t('common.photos.add')}
                        </button>
                    )}

                    {/*
                     * Le dernier mot, et il vient après la photo : celle-ci
                     * reste quelque chose à faire, on ne congédie pas
                     * quelqu'un avant de lui avoir offert.
                     */}
                    <p className="text-brand-muted mt-8 text-center text-base">
                        {t('narrator.record.confirmed_close')}
                    </p>
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
