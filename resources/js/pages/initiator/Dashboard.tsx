import { Head, Link, router } from '@inertiajs/react';

import PhotoGallery from '@/components/PhotoGallery';
import PhotoUploader from '@/components/PhotoUploader';
import { External, Headphones, Pause, Send } from '@/components/space/Icons';
import { PageHeader } from '@/components/space/PageHeader';
import { Pill, type PillTone } from '@/components/space/Pill';
import { ShareSheet } from '@/components/space/ShareSheet';
import { useT } from '@/hooks/useT';
import { formatDate, formatDateTime } from '@/lib/dates';
import { stagger } from '@/lib/motion';

type Photo = {
    id: number | string;
    caption: string | null;
    thumbUrl: string;
    url: string;
    alt: string;
};

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

type Alert = { ruleId: string; firedAt: string; message: string };

type Props = {
    project: {
        id: string;
        status: string;
        statusLabel: string;
        cadence: string;
        cadenceLabel: string;
        promptDay: number;
        promptSlot: string;
        nextPromptAt: string | null;
        pausedUntil: string | null;
        narratorFirstName: string | null;
    };
    stories: Story[];
    hasCurrentStory: boolean;
    alerts: Alert[];
    listensAsFamilyMember: boolean;
    copiedLink: string | null;
    copiedWhatsapp: string | null;
    copiedSms: string | null;
};

/*
 * L'état d'une histoire, en couleur : or pour ce qui attend la narratrice,
 * sauge pour ce qui avance chez elle, marque pour ce qui est partagé, sable
 * pour ce qu'elle a retiré. Jamais la couleur d'action.
 */
const TONES: Record<string, PillTone> = {
    proposed: 'gold',
    recorded: 'sage',
    transcribed: 'sage',
    to_review: 'sage',
    validated: 'brand',
    shared: 'brand',
    in_book: 'brand',
    hidden: 'muted',
    archived: 'muted',
    trashed: 'muted',
    deleted: 'muted',
};

const DOTS: Record<PillTone, string> = {
    gold: 'bg-brand-gold',
    sage: 'bg-brand-sage',
    brand: 'bg-brand',
    muted: 'bg-brand-sand',
};

const PROJECT_TONES: Record<string, PillTone> = {
    active: 'sage',
    awaiting_acceptance: 'gold',
    draft: 'gold',
    paused: 'muted',
    dormant: 'muted',
    completed: 'brand',
    cancelled: 'muted',
    frozen_bereavement: 'muted',
};

function toneFor(state: string): PillTone {
    return TONES[state] ?? 'muted';
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
 * n'apparaît donc qu'après un geste explicite, dans la carte où l'on a cliqué,
 * et le précédent cesse alors de fonctionner.
 */
export default function Dashboard({
    project,
    stories,
    hasCurrentStory,
    alerts,
    listensAsFamilyMember,
    copiedLink,
    copiedWhatsapp,
    copiedSms,
}: Props) {
    const t = useT();
    const name = project.narratorFirstName;

    const title =
        name === null
            ? t('initiator.dashboard.title_generic')
            : t('initiator.dashboard.title', { name });

    const current = stories.find((story) => story.state === 'proposed') ?? null;

    const rhythm =
        project.pausedUntil !== null
            ? t('initiator.dashboard.paused_until', {
                  date: formatDate(project.pausedUntil),
              })
            : project.nextPromptAt === null
              ? t('initiator.dashboard.next_prompt_none')
              : t('initiator.dashboard.next_prompt', {
                    when: formatDateTime(project.nextPromptAt),
                });

    return (
        <>
            <Head title={title} />

            <div className="enter" style={stagger(0)}>
                <PageHeader
                    eyebrow={t('initiator.nav.dashboard')}
                    title={title}
                    intro={
                        <div className="flex flex-wrap items-center gap-x-4 gap-y-2">
                            <Pill
                                tone={PROJECT_TONES[project.status] ?? 'muted'}
                            >
                                {project.statusLabel}
                            </Pill>
                            <span>{rhythm}</span>
                        </div>
                    }
                />
            </div>

            {alerts.length > 0 && (
                <section
                    aria-labelledby="alerts"
                    className="enter mt-8"
                    style={stagger(1)}
                >
                    <h2 id="alerts" className="eyebrow">
                        {t('initiator.dashboard.alerts')}
                    </h2>

                    <ul className="mt-3 flex flex-col gap-3">
                        {alerts.map((alert) => (
                            <li
                                key={`${alert.ruleId}-${alert.firedAt}`}
                                className="panel border-brand-gold border-l-4"
                            >
                                {alert.message}
                            </li>
                        ))}
                    </ul>
                </section>
            )}

            <section
                aria-labelledby="week"
                className="card enter mt-8 p-6"
                style={stagger(2)}
            >
                <h2 id="week" className="eyebrow">
                    {t('initiator.dashboard.this_week')}
                </h2>

                {current !== null ? (
                    <>
                        <span
                            aria-hidden="true"
                            className="bg-brand-gold mt-5 mb-3 block h-px w-10"
                        />
                        <p className="font-display text-brand text-[1.5rem] leading-snug font-medium">
                            {current.question ??
                                t('initiator.dashboard.not_shared_yet')}
                        </p>
                        <p className="text-brand-muted mt-3 text-base">
                            {t('initiator.dashboard.story_number', {
                                n: current.sequence,
                            })}
                            {' · '}
                            {current.label}
                        </p>
                    </>
                ) : (
                    <p className="mt-4">{t('initiator.copy_link.no_story')}</p>
                )}

                <div className="mt-6 flex flex-col gap-4 sm:flex-row sm:items-center">
                    <button
                        type="button"
                        disabled={!hasCurrentStory}
                        onClick={() =>
                            router.post('/espace/lien/question', undefined, {
                                preserveScroll: true,
                            })
                        }
                        className="btn-primary press flex-none disabled:opacity-60"
                    >
                        <Send />
                        {name === null
                            ? t('initiator.dashboard.send_link_generic')
                            : t('initiator.dashboard.send_link', { name })}
                    </button>

                    <p className="text-brand-muted text-base">
                        {t('initiator.dashboard.copy_link_hint')}
                    </p>
                </div>

                {copiedLink !== null && (
                    <ShareSheet
                        link={copiedLink}
                        whatsapp={copiedWhatsapp}
                        sms={copiedSms}
                        title={t('initiator.dashboard.share.title')}
                        hint={t('initiator.dashboard.share.hint')}
                        copyLabel={t('initiator.dashboard.share.copy')}
                        copiedLabel={t('initiator.dashboard.share.copied')}
                        whatsappLabel={t('initiator.dashboard.share.whatsapp')}
                        smsLabel={t('initiator.dashboard.share.sms')}
                    />
                )}
            </section>

            <section
                aria-labelledby="timeline"
                className="enter mt-10"
                style={stagger(3)}
            >
                <h2 id="timeline" className="eyebrow">
                    {t('initiator.dashboard.timeline')}
                </h2>

                <p className="text-brand-muted mt-3 text-base">
                    {t('initiator.dashboard.private_notice', {
                        name: name ?? '',
                    })}
                </p>

                {stories.length === 0 ? (
                    <p className="card mt-5 p-5">
                        {t('initiator.dashboard.timeline_empty')}
                    </p>
                ) : (
                    <ol className="timeline-rail relative mt-5 flex flex-col gap-4 pl-9">
                        {stories.map((story) => {
                            const tone = toneFor(story.state);

                            return (
                                <li key={story.id} className="relative">
                                    <span
                                        aria-hidden="true"
                                        className={`border-brand-background absolute top-5 -left-9 size-[1.375rem] rounded-full border-[3px] ${DOTS[tone]}`}
                                    />

                                    <article className="card p-5">
                                        <div className="flex flex-wrap items-start justify-between gap-3">
                                            <div className="min-w-0">
                                                <p className="text-brand-muted text-[0.8rem] font-semibold tracking-[0.08em] uppercase">
                                                    {t(
                                                        'initiator.dashboard.story_number',
                                                        { n: story.sequence },
                                                    )}
                                                </p>
                                                <p className="font-display text-brand mt-1 text-xl leading-snug font-medium">
                                                    {story.title ??
                                                        story.question ??
                                                        t(
                                                            'initiator.dashboard.not_shared_yet',
                                                        )}
                                                </p>
                                            </div>

                                            <Pill tone={tone}>
                                                {story.label}
                                            </Pill>
                                        </div>

                                        {story.sharedAt !== null ? (
                                            <p className="text-brand-muted mt-2 text-base">
                                                {t(
                                                    'initiator.dashboard.shared_on',
                                                    {
                                                        date: formatDate(
                                                            story.sharedAt,
                                                        ),
                                                    },
                                                )}
                                            </p>
                                        ) : story.recordedAt !== null ? (
                                            <p className="text-brand-muted mt-2 text-base">
                                                {t(
                                                    'initiator.dashboard.recorded_on',
                                                    {
                                                        date: formatDate(
                                                            story.recordedAt,
                                                        ),
                                                    },
                                                )}
                                            </p>
                                        ) : null}

                                        {/*
                                         * Ses photos, et seulement les siennes
                                         * tant que l'histoire n'est pas
                                         * partagée : une photo est du contenu,
                                         * comme le texte et la voix. Le serveur
                                         * filtre ; l'écran n'a rien à décider.
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
                                    </article>
                                </li>
                            );
                        })}
                    </ol>
                )}
            </section>

            {listensAsFamilyMember && (
                <section
                    aria-labelledby="listen"
                    className="card enter mt-10 flex flex-col gap-4 p-6 sm:flex-row sm:items-center sm:justify-between"
                    style={stagger(4)}
                >
                    <div>
                        <h2
                            id="listen"
                            className="font-display text-brand text-xl leading-snug font-medium"
                        >
                            {t('initiator.dashboard.listen')}
                        </h2>
                        <p className="text-brand-muted mt-1 text-base">
                            {t('initiator.dashboard.listen_hint')}
                        </p>
                    </div>

                    <a
                        href="/espace/ecoute"
                        target="_blank"
                        rel="noopener"
                        className="btn-secondary press flex-none"
                    >
                        <Headphones />
                        {t('initiator.dashboard.listen_open')}
                        <External className="size-4" />
                    </a>
                </section>
            )}

            <p className="enter mt-10 text-base" style={stagger(5)}>
                <Link
                    href="/espace/reglages"
                    className="text-brand-muted hover:text-brand inline-flex items-center gap-2 underline underline-offset-4 transition-colors"
                >
                    <Pause className="size-4" />
                    {t('initiator.dashboard.pause')}
                </Link>
            </p>
        </>
    );
}
