import { Head } from '@inertiajs/react';
import { useCallback, useEffect, useMemo, useRef, useState } from 'react';

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
import { isRecordingSupported } from '@/recorder/mime';
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

import PhotoUploader from '@/components/PhotoUploader';

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
};

function formatDuration(seconds: number): string {
    const minutes = Math.floor(seconds / 60);
    const rest = seconds % 60;

    return `${minutes}:${String(rest).padStart(2, '0')}`;
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
}: Props) {
    const t = useT();
    const basePath = window.location.pathname;

    const [snapshot, setSnapshot] = useState<RecorderSnapshot>(initialSnapshot);
    const [draft, setDraft] = useState<Draft | null>(null);
    const [progress, setProgress] = useState(0);
    const [addingPhoto, setAddingPhoto] = useState(false);
    const [reviewUrl, setReviewUrl] = useState<string | null>(null);
    const [writing, setWriting] = useState(false);
    const [roomWarning, setRoomWarning] = useState(false);
    const [decided, setDecided] = useState<string | null>(shareDecision);

    const recorder = useMediaRecorder(storyRef, limits.segmentMilliseconds);
    const startedAt = useRef<number | null>(null);
    const wakeLock = useRef<{ release: () => void } | null>(null);

    const send = useCallback(
        (event: RecorderEvent) => {
            setSnapshot((current) => reduce(current, event, limits));
        },
        [limits],
    );

    const tu = addressForm === 'tu';

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
        send({ type: 'READY' });

        const granted = await recorder.requestPermission();

        if (!granted) {
            reportClientEvent('mic_denied', { platform: detectPlatform() });
            send({ type: 'PERMISSION_DENIED' });

            return;
        }

        reportClientEvent('mic_granted');
        send({ type: 'PERMISSION_GRANTED' });
    };

    const startRecording = async () => {
        await recorder.start();
        wakeLock.current = await requestWakeLock();
        startedAt.current = Date.now();
        reportClientEvent('recording_started');
        send({ type: 'RECORD' });
    };

    const finish = async () => {
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
                canRetry={snapshot.state === 'permission_denied'}
                onRetry={() => void askPermission()}
                onWrite={() => {
                    reportClientEvent('written_answer_chosen');
                    setWriting(true);
                }}
            />
        );
    }

    return (
        <>
            <Head title={greeting} />

            <h1 className="font-display text-2xl leading-tight font-semibold sm:text-3xl">
                {greeting}
            </h1>

            {question !== null ? (
                <p className="bg-brand-linen text-brand-text mt-6 rounded-md px-4 py-5 text-[1.5rem] leading-snug">
                    {question}
                </p>
            ) : null}

            {roomWarning ? (
                <p role="status" className="mt-6 text-base">
                    {t('narrator.record.storage_low')}
                </p>
            ) : null}

            {snapshot.state === 'draft_found' ? (
                <section className="mt-8">
                    <h2 className="text-xl font-semibold">
                        {t('narrator.record.draft_title')}
                    </h2>
                    <p className="mt-3">{t('narrator.record.draft_body')}</p>

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
                        className="bg-brand text-brand-foreground mt-6 min-h-[2.75rem] w-full rounded-md px-6 py-3 text-lg font-medium"
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
                        className="border-brand-sand mt-4 min-h-[2.75rem] w-full rounded-md border px-6 py-3 text-lg font-medium"
                    >
                        {t('narrator.record.draft_discard')}
                    </button>
                </section>
            ) : null}

            {snapshot.state === 'explaining' ? (
                <section className="mt-8">
                    <p className="bg-brand-surface border-brand-sand rounded-md border px-4 py-4">
                        {t(
                            tu
                                ? 'narrator.record.mic_notice_tu'
                                : 'narrator.record.mic_notice',
                        )}
                    </p>

                    <button
                        type="button"
                        onClick={() => void askPermission()}
                        className="bg-brand text-brand-foreground mt-8 min-h-[2.75rem] w-full rounded-md px-6 py-4 text-xl font-medium"
                    >
                        {t('narrator.record.ready')}
                    </button>
                </section>
            ) : null}

            {snapshot.state === 'requesting_permission' ? (
                <p role="status" className="mt-8">
                    {t('narrator.record.requesting')}
                </p>
            ) : null}

            {snapshot.state === 'ready' ? (
                <button
                    type="button"
                    onClick={() => void startRecording()}
                    className="bg-brand text-brand-foreground mt-8 min-h-[5.5rem] w-full rounded-full px-6 py-6 text-2xl font-medium"
                >
                    {t('narrator.record.start')}
                </button>
            ) : null}

            {snapshot.state === 'recording' || snapshot.state === 'paused' ? (
                <section className="mt-8">
                    <p role="status" className="text-lg">
                        {snapshot.state === 'recording'
                            ? t('narrator.record.recording')
                            : t('narrator.record.paused')}
                    </p>

                    <p className="mt-2 text-2xl tabular-nums">
                        {t('narrator.record.elapsed', {
                            time: formatDuration(
                                snapshot.context.elapsedSeconds,
                            ),
                        })}
                    </p>

                    {snapshot.context.warningShown ? (
                        <p className="mt-4 text-base">
                            {t('narrator.record.soft_warning')}
                        </p>
                    ) : null}

                    <button
                        type="button"
                        onClick={() => {
                            if (snapshot.state === 'recording') {
                                recorder.pause();
                                reportClientEvent('recording_paused');
                                send({ type: 'PAUSE' });
                            } else {
                                recorder.resume();
                                reportClientEvent('recording_resumed');
                                send({ type: 'RESUME' });
                            }
                        }}
                        className="border-brand-sand mt-8 min-h-[2.75rem] w-full rounded-md border px-6 py-4 text-xl font-medium"
                    >
                        {snapshot.state === 'recording'
                            ? t('narrator.record.pause')
                            : t('narrator.record.resume')}
                    </button>

                    <button
                        type="button"
                        onClick={() => void finish()}
                        className="bg-brand text-brand-foreground mt-4 min-h-[2.75rem] w-full rounded-md px-6 py-4 text-xl font-medium"
                    >
                        {t('narrator.record.finish')}
                    </button>
                </section>
            ) : null}

            {snapshot.state === 'interrupted' ? (
                <section className="mt-8">
                    <p role="status">{t('narrator.record.interrupted')}</p>

                    <button
                        type="button"
                        onClick={() => {
                            void recorder.startNewSegment().then(() => {
                                send({ type: 'RESUME_AFTER_INTERRUPTION' });
                            });
                        }}
                        className="bg-brand text-brand-foreground mt-6 min-h-[2.75rem] w-full rounded-md px-6 py-4 text-xl font-medium"
                    >
                        {t('narrator.record.interrupted_resume')}
                    </button>

                    <button
                        type="button"
                        onClick={() => void finish()}
                        className="border-brand-sand mt-4 min-h-[2.75rem] w-full rounded-md border px-6 py-4 text-xl font-medium"
                    >
                        {t('narrator.record.finish')}
                    </button>
                </section>
            ) : null}

            {snapshot.state === 'stopping' ? (
                <p role="status" className="mt-8">
                    {snapshot.context.hardStopReached
                        ? t('narrator.record.hard_stop')
                        : t('narrator.record.uploading')}
                </p>
            ) : null}

            {snapshot.state === 'reviewing' ? (
                <section className="mt-8">
                    <h2 className="text-xl font-semibold">
                        {t('narrator.record.review_title')}
                    </h2>

                    {reviewUrl !== null ? (
                        <audio
                            controls
                            src={reviewUrl}
                            className="mt-4 w-full"
                            aria-label={t('narrator.record.listen')}
                        />
                    ) : null}

                    <button
                        type="button"
                        onClick={() => void upload()}
                        className="bg-brand text-brand-foreground mt-8 min-h-[2.75rem] w-full rounded-md px-6 py-4 text-xl font-medium"
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
                        className="border-brand-sand mt-4 min-h-[2.75rem] w-full rounded-md border px-6 py-3 text-lg font-medium"
                    >
                        {t('narrator.record.restart')}
                    </button>
                </section>
            ) : null}

            {snapshot.state === 'uploading' ? (
                <section className="mt-8">
                    <p role="status" className="text-lg">
                        {t('narrator.record.uploading')}
                    </p>

                    <progress
                        value={progress}
                        max={1}
                        className="mt-4 w-full"
                        aria-label={t('narrator.record.uploading')}
                    />

                    <p className="mt-4 text-base">
                        {t('narrator.record.uploading_notice')}
                    </p>
                </section>
            ) : null}

            {snapshot.state === 'upload_failed' ? (
                <section className="mt-8">
                    <h2 className="text-xl font-semibold">
                        {t('narrator.record.upload_failed_title')}
                    </h2>
                    <p className="mt-3">
                        {t('narrator.record.upload_failed_body')}
                    </p>

                    <button
                        type="button"
                        onClick={() => {
                            reportClientEvent('upload_retried');
                            send({ type: 'RETRY_UPLOAD' });
                            void upload();
                        }}
                        className="bg-brand text-brand-foreground mt-6 min-h-[2.75rem] w-full rounded-md px-6 py-4 text-xl font-medium"
                    >
                        {t('narrator.record.retry')}
                    </button>
                </section>
            ) : null}

            {snapshot.state === 'confirmed' ? (
                <section className="mt-8">
                    <h2 role="status" className="text-2xl font-semibold">
                        {t('narrator.record.confirmed_title')}
                    </h2>
                    <p className="mt-3 text-lg">
                        {t('narrator.record.confirmed_body', {
                            name: firstName,
                        })}
                    </p>

                    {/*
                     * Variante A : la question se pose maintenant, pendant que
                     * le narrateur est encore là. C'est tout l'objet du test
                     * de Phase 0A — la validation comme récompense d'un tap.
                     */}
                    {validationVariant === 'immediate' ? (
                        decided === null ? (
                            <ShareDecision
                                action={shareDecisionAction}
                                onDecided={setDecided}
                            />
                        ) : (
                            <p
                                role="status"
                                className="bg-brand-linen text-brand-text mt-8 rounded-md px-4 py-3"
                            >
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
                     * récit à mi-chemin.
                     *
                     * Facultatif de bout en bout : replié derrière un lien,
                     * et sans conséquence si on l'ignore.
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
                            className="border-brand-sand mt-8 min-h-[2.75rem] rounded-md border px-6 py-3 text-lg"
                        >
                            {t('common.photos.add')}
                        </button>
                    )}
                </section>
            ) : null}

            {snapshot.state === 'explaining' || snapshot.state === 'ready' ? (
                <button
                    type="button"
                    onClick={() => {
                        reportClientEvent('written_answer_chosen');
                        setWriting(true);
                    }}
                    className="text-brand-muted mt-10 min-h-[2.75rem] w-full text-base underline"
                >
                    {t('narrator.record.written_link')}
                </button>
            ) : null}
        </>
    );
}
