import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

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
 * « Vos histoires » : une carte par histoire, et les gestes qui vont avec.
 *
 * Chaque retrait demande une confirmation à l'écran avant de partir, et la
 * suppression demande en plus le mot SUPPRIMER. Le but n'est pas de dissuader
 * mais d'éviter le geste involontaire : sur un téléphone tenu à bout de bras,
 * un bouton se touche sans le vouloir.
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

    return (
        <>
            <Head title={t('narrator.space.title')} />

            <h1 className="font-display text-2xl leading-tight font-semibold sm:text-3xl">
                {t('narrator.space.title')}
            </h1>

            {status !== null ? (
                <p
                    role="status"
                    className="bg-brand-linen text-brand-text mt-6 rounded-md px-4 py-3"
                >
                    {status}
                </p>
            ) : null}

            {pausedUntil !== null ? (
                <p className="border-brand-sand mt-6 rounded-md border px-4 py-3">
                    {t('narrator.space.paused_until', {
                        date: longDate(pausedUntil),
                    })}
                </p>
            ) : null}

            {stories.length === 0 ? (
                <p className="mt-8">{t('narrator.space.empty')}</p>
            ) : (
                <ul className="mt-8 flex flex-col gap-6">
                    {stories.map((story) => (
                        <li
                            key={story.id}
                            className="border-brand-sand rounded-md border px-4 py-4"
                        >
                            <h2 className="text-lg font-medium">
                                {story.title ?? story.question}
                            </h2>

                            <p className="text-brand-muted mt-1 text-base">
                                {story.label}
                                {story.recordedAt === null
                                    ? ''
                                    : ` · ${longDate(story.recordedAt)}`}
                            </p>

                            {story.restorableUntil !== null ? (
                                <p className="mt-2 text-base">
                                    {t('narrator.space.restorable_until', {
                                        date: longDate(story.restorableUntil),
                                    })}
                                </p>
                            ) : null}

                            {story.printedInBook ? (
                                <p className="border-brand-sand mt-3 rounded-md border px-3 py-2 text-base">
                                    {printedCopiesWarning}
                                </p>
                            ) : null}

                            <div className="mt-4 flex flex-col gap-3">
                                {story.state === 'hidden' ? (
                                    <button
                                        type="button"
                                        onClick={() => act(story, 'unhide')}
                                        className="border-brand-sand min-h-[2.75rem] rounded-md border px-4 py-3 text-left text-lg"
                                    >
                                        {t('narrator.withdrawals.unhide')}
                                    </button>
                                ) : null}

                                {story.state === 'trashed' ? (
                                    <button
                                        type="button"
                                        onClick={() => act(story, 'restore')}
                                        className="border-brand-sand min-h-[2.75rem] rounded-md border px-4 py-3 text-left text-lg"
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
                                        className="border-brand-sand min-h-[2.75rem] rounded-md border px-4 py-3 text-left text-lg"
                                    >
                                        {t('narrator.withdrawals.hide')}
                                    </button>
                                ) : null}

                                {story.state !== 'trashed' &&
                                story.state !== 'deleted' ? (
                                    confirming === story.id ? (
                                        <div className="border-brand-sand rounded-md border px-3 py-3">
                                            <p className="text-base">
                                                {t(
                                                    'narrator.withdrawals.trash_confirm',
                                                )}
                                            </p>
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    act(story, 'trash')
                                                }
                                                className="bg-brand text-brand-foreground mt-3 min-h-[2.75rem] w-full rounded-md px-4 py-3 text-lg"
                                            >
                                                {t(
                                                    'narrator.withdrawals.trash',
                                                )}
                                            </button>
                                        </div>
                                    ) : (
                                        <button
                                            type="button"
                                            onClick={() =>
                                                setConfirming(story.id)
                                            }
                                            className="border-brand-sand min-h-[2.75rem] rounded-md border px-4 py-3 text-left text-lg"
                                        >
                                            {t('narrator.withdrawals.trash')}
                                        </button>
                                    )
                                ) : null}

                                {story.state === 'trashed' ? (
                                    deleting === story.id ? (
                                        <div className="border-brand-sand rounded-md border px-3 py-3">
                                            <p className="text-base">
                                                {t(
                                                    'narrator.withdrawals.delete_confirm',
                                                )}
                                            </p>
                                            <label
                                                htmlFor={`word-${story.id}`}
                                                className="mt-3 block text-base font-medium"
                                            >
                                                {t(
                                                    'narrator.withdrawals.delete_word_label',
                                                )}
                                            </label>
                                            <input
                                                id={`word-${story.id}`}
                                                value={word}
                                                onChange={(event) =>
                                                    setWord(event.target.value)
                                                }
                                                className="border-brand-sand mt-2 min-h-[2.75rem] w-full rounded-md border px-3 py-2 text-lg"
                                            />
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    act(story, 'delete', {
                                                        confirmation: word,
                                                    })
                                                }
                                                className="bg-brand text-brand-foreground mt-3 min-h-[2.75rem] w-full rounded-md px-4 py-3 text-lg"
                                            >
                                                {t(
                                                    'narrator.withdrawals.delete',
                                                )}
                                            </button>
                                        </div>
                                    ) : (
                                        <button
                                            type="button"
                                            onClick={() =>
                                                setDeleting(story.id)
                                            }
                                            className="border-brand-sand min-h-[2.75rem] rounded-md border px-4 py-3 text-left text-lg"
                                        >
                                            {t('narrator.withdrawals.delete')}
                                        </button>
                                    )
                                ) : null}
                            </div>

                            {/*
                             * Les photos de l'histoire, sous les gestes de
                             * retrait. Le narrateur retire n'importe laquelle
                             * — y compris ce qu'un proche a joint à son
                             * récit : c'est le sien.
                             */}
                            <PhotoGallery
                                photos={story.photos}
                                onRemove={(id) =>
                                    router.delete(
                                        `${window.location.pathname}/stories/${story.id}/photos/${id}`,
                                        { preserveScroll: true },
                                    )
                                }
                            />

                            <PhotoUploader
                                action={`${window.location.pathname}/stories/${story.id}/photos`}
                            />
                        </li>
                    ))}
                </ul>
            )}

            <form
                className="mt-12"
                onSubmit={(event) => {
                    event.preventDefault();
                    const weeks = new FormData(event.currentTarget).get(
                        'weeks',
                    );
                    router.post(`${window.location.pathname}/pause`, {
                        weeks: Number(weeks),
                    });
                }}
            >
                <label htmlFor="weeks" className="block text-lg font-medium">
                    {t('narrator.space.pause_weeks')}
                </label>
                <input
                    id="weeks"
                    name="weeks"
                    type="number"
                    min={1}
                    max={26}
                    defaultValue={4}
                    className="border-brand-sand mt-3 min-h-[2.75rem] w-24 rounded-md border px-3 py-2 text-lg"
                />
                <button
                    type="submit"
                    className="border-brand-sand mt-4 block min-h-[2.75rem] rounded-md border px-6 py-3 text-lg"
                >
                    {t('narrator.space.pause')}
                </button>
            </form>
        </>
    );
}
