import { Head, Link, router } from '@inertiajs/react';

import PhotoGallery, { type Photo } from '@/components/PhotoGallery';
import PhotoUploader from '@/components/PhotoUploader';
import { useT } from '@/hooks/useT';

type Story = {
    id: string;
    sequence: number;
    state: string;
    label: string;
    question: string | null;
    title: string | null;
    recordedAt: string | null;
    sharedAt: string | null;
    photos: Photo[];
};

type Props = {
    project: {
        id: string;
        status: string;
        statusLabel: string;
        cadence: string;
        promptDay: number;
        promptSlot: string;
        nextPromptAt: string | null;
        pausedUntil: string | null;
        narratorFirstName: string | null;
    };
    stories: Story[];
    hasCurrentStory: boolean;
    alerts: { ruleId: string; firedAt: string; message: string }[];
    listensAsFamilyMember: boolean;
    copiedLink: string | null;
    copiedWhatsapp: string | null;
};

function formatDateTime(iso: string | null): string {
    if (iso === null) {
        return '';
    }

    return new Intl.DateTimeFormat('fr-FR', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(iso));
}

function formatDate(iso: string): string {
    return new Intl.DateTimeFormat('fr-FR', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    }).format(new Date(iso));
}

/**
 * Le tableau de bord de l'Initiateur·rice.
 *
 * Elle voit **où en est** chaque histoire, jamais son contenu tant que le
 * narrateur ne l'a pas partagée — titre compris, parce qu'un titre est déjà du
 * contenu. C'est le même invariant que pour les proches, et il vaut aussi pour
 * celle qui paie.
 *
 * Le lien de la semaine se **réémet** : les jetons sont stockés hachés, un
 * lien en clair n'existe qu'entre son émission et son envoi (bloc 03). Il
 * n'apparaît donc qu'après un geste explicite, et le précédent cesse alors de
 * fonctionner.
 */
export default function Dashboard({
    project,
    stories,
    hasCurrentStory,
    alerts,
    listensAsFamilyMember,
    copiedLink,
    copiedWhatsapp,
}: Props) {
    const t = useT();
    const name = project.narratorFirstName;

    return (
        <>
            <Head
                title={
                    name === null
                        ? t('initiator.dashboard.title_generic')
                        : t('initiator.dashboard.title', { name })
                }
            />

            <h1 className="font-display text-2xl leading-tight font-semibold">
                {name === null
                    ? t('initiator.dashboard.title_generic')
                    : t('initiator.dashboard.title', { name })}
            </h1>

            <p className="text-brand-muted mt-2">{project.statusLabel}</p>

            {project.pausedUntil !== null ? (
                <p className="mt-4">
                    {t('initiator.dashboard.paused_until', {
                        date: formatDate(project.pausedUntil),
                    })}
                </p>
            ) : (
                <p className="mt-4">
                    {project.nextPromptAt === null
                        ? t('initiator.dashboard.next_prompt_none')
                        : t('initiator.dashboard.next_prompt', {
                              when: formatDateTime(project.nextPromptAt),
                          })}
                </p>
            )}

            {alerts.length > 0 && (
                <section aria-labelledby="alerts" className="mt-8">
                    <h2 id="alerts" className="text-xl font-medium">
                        {t('initiator.dashboard.alerts')}
                    </h2>

                    <ul className="mt-3 flex flex-col gap-3">
                        {alerts.map((alert) => (
                            <li
                                key={`${alert.ruleId}-${alert.firedAt}`}
                                className="border-brand-muted/40 rounded-md border px-4 py-3"
                            >
                                {alert.message}
                            </li>
                        ))}
                    </ul>
                </section>
            )}

            <section aria-labelledby="link" className="mt-10">
                <h2 id="link" className="text-xl font-medium">
                    {t('initiator.dashboard.copy_link')}
                </h2>

                <p className="text-brand-muted mt-2 text-base">
                    {t('initiator.dashboard.copy_link_hint')}
                </p>

                <button
                    type="button"
                    disabled={!hasCurrentStory}
                    onClick={() =>
                        router.post('/espace/lien/question', undefined, {
                            preserveScroll: true,
                        })
                    }
                    className="border-brand-muted/40 mt-4 min-h-[2.75rem] rounded-md border px-6 py-3 disabled:opacity-60"
                >
                    {t('initiator.dashboard.copy_link')}
                </button>

                {copiedLink !== null && (
                    <div className="mt-4">
                        <p className="text-base">
                            {t('initiator.dashboard.copied')}
                        </p>

                        <input
                            type="text"
                            readOnly
                            value={copiedLink}
                            onFocus={(event) => event.target.select()}
                            className="input mt-2"
                            aria-label={t('initiator.dashboard.copied')}
                        />

                        {copiedWhatsapp !== null && (
                            <a
                                href={copiedWhatsapp}
                                className="border-brand-muted/40 mt-3 inline-block min-h-[2.75rem] rounded-md border px-6 py-3"
                            >
                                {t('initiator.dashboard.send_whatsapp')}
                            </a>
                        )}
                    </div>
                )}
            </section>

            <section aria-labelledby="timeline" className="mt-10">
                <h2 id="timeline" className="text-xl font-medium">
                    {t('initiator.dashboard.timeline')}
                </h2>

                <p className="text-brand-muted mt-2 text-base">
                    {t('initiator.dashboard.private_notice', {
                        name: name ?? '',
                    })}
                </p>

                {stories.length === 0 ? (
                    <p className="mt-4">
                        {t('initiator.dashboard.timeline_empty')}
                    </p>
                ) : (
                    <ol className="mt-4 flex flex-col gap-4">
                        {stories.map((story) => (
                            <li
                                key={story.id}
                                className="border-brand-muted/40 rounded-md border px-4 py-3"
                            >
                                <p className="font-medium">
                                    {story.title ??
                                        story.question ??
                                        t('initiator.dashboard.not_shared_yet')}
                                </p>
                                <p className="text-brand-muted mt-1 text-base">
                                    {story.label}
                                </p>

                                {/*
                                 * Ses photos, et seulement les siennes tant
                                 * que l'histoire n'est pas partagée : une
                                 * photo est du contenu, comme le texte et la
                                 * voix. Le serveur filtre ; l'écran n'a rien
                                 * à décider.
                                 */}
                                <PhotoGallery
                                    photos={story.photos}
                                    onRemove={(id) =>
                                        router.delete(
                                            `/espace/histoires/${story.id}/photos/${id}`,
                                            { preserveScroll: true },
                                        )
                                    }
                                />

                                <PhotoUploader
                                    action={`/espace/histoires/${story.id}/photos`}
                                />
                            </li>
                        ))}
                    </ol>
                )}
            </section>

            {listensAsFamilyMember && (
                <section aria-labelledby="listen" className="mt-10">
                    <h2 id="listen" className="text-xl font-medium">
                        {t('initiator.dashboard.listen')}
                    </h2>

                    <p className="text-brand-muted mt-2 text-base">
                        {t('initiator.dashboard.listen_hint')}
                    </p>

                    <button
                        type="button"
                        onClick={() =>
                            router.post('/espace/lien/ecoute', undefined, {
                                preserveScroll: true,
                            })
                        }
                        className="border-brand-muted/40 mt-4 min-h-[2.75rem] rounded-md border px-6 py-3"
                    >
                        {t('initiator.dashboard.listen')}
                    </button>
                </section>
            )}

            <Link
                href="/espace/reglages"
                className="text-brand-muted mt-10 inline-block underline"
            >
                {t('initiator.dashboard.pause')}
            </Link>
        </>
    );
}
