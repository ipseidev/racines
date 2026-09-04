import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

import { Counter } from '@/components/form/Counter';
import { TextField } from '@/components/form/TextField';
import PhotoGallery, { type Photo } from '@/components/PhotoGallery';
import PhotoUploader from '@/components/PhotoUploader';
import { useT } from '@/hooks/useT';

type Story = {
    id: string;
    title: string | null;
    question: string | null;
    state: string;
    label: string;
    recordedAt: string | null;
    visibility: string;
    printedInBook: boolean;
    restorableUntil: string | null;
    photos: Photo[];
};

type Props = {
    firstName: string;
    stories: Story[];
    pausedUntil: string | null;
    printedCopiesWarning: string;
};

const longDate = (iso: string) =>
    new Intl.DateTimeFormat('fr-FR', { dateStyle: 'long' }).format(
        new Date(iso),
    );

/**
 * L'espace de la narratrice : ses histoires, et ce qu'elle peut en faire.
 *
 * Chaque histoire est une carte : son titre en Fraunces, où elle en est de
 * son point de vue (jamais un état technique), la date, ses photos, puis les
 * gestes possibles, du plus doux au définitif. Les confirmations s'ouvrent
 * dans la carte, en lin : on ne quitte pas l'histoire pour décider d'elle.
 */
export default function Space({
    stories,
    pausedUntil,
    printedCopiesWarning,
}: Props) {
    const t = useT();
    const status =
        (usePage().props.flash as { status?: string | null } | undefined)
            ?.status ?? null;

    const [confirming, setConfirming] = useState<string | null>(null);
    const [deleting, setDeleting] = useState<string | null>(null);
    const [word, setWord] = useState('');
    const [weeks, setWeeks] = useState(4);

    const act = (
        story: Story,
        action: string,
        data: Record<string, string> = {},
    ) => {
        router.post(
            `${window.location.pathname}/stories/${story.id}/${action}`,
            data,
            {
                preserveScroll: true,
                onFinish: () => {
                    setConfirming(null);
                    setDeleting(null);
                    setWord('');
                },
            },
        );
    };

    const quiet =
        'btn-secondary press min-h-[2.75rem] px-4 py-2.5 text-base font-medium';
    const dangerLink =
        'text-brand-muted hover:text-brand min-h-[2.75rem] px-2 text-base underline underline-offset-4 transition-colors';

    return (
        <>
            <Head title={t('narrator.space.title')} />

            <h1 className="font-display text-[2rem] leading-tight font-medium">
                {t('narrator.space.title')}
            </h1>

            {status !== null ? (
                <p role="status" className="panel enter mt-6">
                    {status}
                </p>
            ) : null}

            {pausedUntil !== null ? (
                <p className="chip mt-6">
                    {t('narrator.space.paused_until', {
                        date: longDate(pausedUntil),
                    })}
                </p>
            ) : null}

            {stories.length === 0 ? (
                <div className="card mt-8 px-6 py-8 text-center">
                    <p className="font-display text-brand text-2xl">
                        {t('narrator.space.empty')}
                    </p>
                    <p className="text-brand-muted mt-2 text-base">
                        {t('narrator.space.empty_hint')}
                    </p>
                </div>
            ) : (
                <ul className="mt-8 flex flex-col gap-5">
                    {stories.map((story) => (
                        <li key={story.id} className="card p-5">
                            <h2 className="font-display text-brand text-2xl leading-tight font-medium">
                                {story.title ?? story.question}
                            </h2>
                            <p className="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-base">
                                <span className="chip">{story.label}</span>
                                {story.recordedAt === null ? null : (
                                    <span className="text-brand-muted">
                                        {longDate(story.recordedAt)}
                                    </span>
                                )}
                            </p>

                            {story.restorableUntil !== null ? (
                                <p className="text-brand-muted mt-3 text-base">
                                    {t('narrator.space.restorable_until', {
                                        date: longDate(story.restorableUntil),
                                    })}
                                </p>
                            ) : null}

                            {story.printedInBook ? (
                                <p className="panel mt-4 text-base">
                                    {printedCopiesWarning}
                                </p>
                            ) : null}

                            <PhotoGallery
                                photos={story.photos}
                                onRemove={(id) =>
                                    router.delete(
                                        `${window.location.pathname}/stories/${story.id}/photos/${id}`,
                                        { preserveScroll: true },
                                    )
                                }
                            />

                            {story.state !== 'trashed' &&
                            story.state !== 'deleted' ? (
                                <PhotoUploader
                                    action={`${window.location.pathname}/stories/${story.id}/photos`}
                                />
                            ) : null}

                            <div className="border-brand-sand mt-6 flex flex-col gap-3 border-t pt-5">
                                <p className="text-brand-muted text-base">
                                    {t('narrator.space.actions')}
                                </p>

                                <div className="flex flex-wrap items-center gap-3">
                                    {story.state === 'hidden' ? (
                                        <button
                                            type="button"
                                            onClick={() => act(story, 'unhide')}
                                            className={quiet}
                                        >
                                            {t('narrator.withdrawals.unhide')}
                                        </button>
                                    ) : null}

                                    {story.state === 'trashed' ? (
                                        <button
                                            type="button"
                                            onClick={() =>
                                                act(story, 'restore')
                                            }
                                            className={quiet}
                                        >
                                            {t('narrator.withdrawals.restore')}
                                        </button>
                                    ) : null}

                                    {story.state !== 'hidden' &&
                                    story.state !== 'trashed' &&
                                    story.state !== 'deleted' ? (
                                        <button
                                            type="button"
                                            onClick={() => act(story, 'hide')}
                                            className={quiet}
                                        >
                                            {t('narrator.withdrawals.hide')}
                                        </button>
                                    ) : null}

                                    {story.state !== 'trashed' &&
                                    story.state !== 'deleted' &&
                                    confirming !== story.id ? (
                                        <button
                                            type="button"
                                            onClick={() =>
                                                setConfirming(story.id)
                                            }
                                            className={dangerLink}
                                        >
                                            {t('narrator.withdrawals.trash')}
                                        </button>
                                    ) : null}

                                    {story.state === 'trashed' &&
                                    deleting !== story.id ? (
                                        <button
                                            type="button"
                                            onClick={() =>
                                                setDeleting(story.id)
                                            }
                                            className={dangerLink}
                                        >
                                            {t('narrator.withdrawals.delete')}
                                        </button>
                                    ) : null}
                                </div>

                                {confirming === story.id ? (
                                    <div className="panel enter flex flex-col gap-3">
                                        <p className="text-base">
                                            {t(
                                                'narrator.withdrawals.trash_confirm',
                                            )}
                                        </p>
                                        <div className="flex flex-wrap gap-3">
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    act(story, 'trash')
                                                }
                                                className="btn-primary press min-h-[2.75rem] py-2.5 text-base"
                                            >
                                                {t(
                                                    'narrator.withdrawals.trash',
                                                )}
                                            </button>
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    setConfirming(null)
                                                }
                                                className={dangerLink}
                                            >
                                                {t('common.actions.cancel')}
                                            </button>
                                        </div>
                                    </div>
                                ) : null}

                                {deleting === story.id ? (
                                    <div className="panel enter flex flex-col gap-4">
                                        <p className="text-base">
                                            {t(
                                                'narrator.withdrawals.delete_confirm',
                                            )}
                                        </p>
                                        <TextField
                                            id={`word-${story.id}`}
                                            label={t(
                                                'narrator.withdrawals.delete_word_label',
                                            )}
                                            type="text"
                                            value={word}
                                            onChange={(event) =>
                                                setWord(event.target.value)
                                            }
                                            autoComplete="off"
                                            autoCapitalize="characters"
                                            className="text-lg tracking-wide"
                                        />
                                        <div className="flex flex-wrap gap-3">
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    act(story, 'delete', {
                                                        confirmation: word,
                                                    })
                                                }
                                                className="btn-primary press min-h-[2.75rem] py-2.5 text-base"
                                            >
                                                {t(
                                                    'narrator.withdrawals.delete',
                                                )}
                                            </button>
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    setDeleting(null)
                                                }
                                                className={dangerLink}
                                            >
                                                {t('common.actions.cancel')}
                                            </button>
                                        </div>
                                    </div>
                                ) : null}
                            </div>
                        </li>
                    ))}
                </ul>
            )}

            {/* La pause ========================================================= */}
            <form
                className="card mt-10 flex flex-col gap-4 p-5"
                onSubmit={(event) => {
                    event.preventDefault();
                    router.post(`${window.location.pathname}/pause`, {
                        weeks,
                    });
                }}
            >
                <h2 className="font-display text-brand text-2xl leading-tight font-medium">
                    {t('narrator.space.pause_title')}
                </h2>
                <p className="text-brand-muted text-base">
                    {t('narrator.space.pause_body')}
                </p>

                <Counter
                    id="weeks"
                    name="weeks"
                    label={t('narrator.space.pause_weeks')}
                    value={weeks}
                    min={1}
                    max={26}
                    onChange={setWeeks}
                    decrementLabel={t('narrator.space.pause_fewer')}
                    incrementLabel={t('narrator.space.pause_more')}
                />

                <button
                    type="submit"
                    className="btn-secondary press self-start"
                >
                    {t('narrator.space.pause')}
                </button>
            </form>
        </>
    );
}
