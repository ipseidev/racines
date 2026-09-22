import { Head, Link, router } from '@inertiajs/react';

import { useFormat } from '@/hooks/useFormat';
import PhotoGallery from '@/components/PhotoGallery';
import PhotoUploader from '@/components/PhotoUploader';
import { Gauge } from '@/components/space/Gauge';
import { External, Headphones, Pause, Send } from '@/components/space/Icons';
import { PageHeader } from '@/components/space/PageHeader';
import { Pill, type PillTone } from '@/components/space/Pill';
import { ShareSheet } from '@/components/space/ShareSheet';
import { useSpacePath } from '@/hooks/useSpacePath';
import { useT } from '@/hooks/useT';
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

/** Une question qui n'est pas encore partie, telle que `QuestionQueue` la rend. */
type Upcoming = {
    kind: 'story' | 'question';
    id: string;
    text: string;
    theme: string | null;
    themeLabel: string | null;
    photos: number;
    sendAt: string | null;
};

type Readiness = {
    words: number;
    audioMinutes: number;
    estimatedPages: number;
    themes: number;
    ready: boolean;
    thresholds: {
        words: number;
        audioMinutes: number;
        pages: number;
        themes: number;
    };
};

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
    upcoming: Upcoming[];
    readiness: Readiness;
    counts: {
        asked: number;
        recorded: number;
        shared: number;
        /*
         * Nuls tant que la collecte n'a pas commencé.
         *
         * `planned` est ce que l'offre vend — 52 questions, quel que soit le
         * rythme (R-2). `endsAt` est la date où la dernière partira : elle
         * dépend du rythme, elle change quand il change, et c'est pour ça
         * qu'elle s'affiche comme une date et non comme un dénominateur.
         */
        planned: number | null;
        endsAt: string | null;
    };
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
    upcoming,
    readiness,
    counts,
    hasCurrentStory,
    alerts,
    listensAsFamilyMember,
    copiedLink,
    copiedWhatsapp,
    copiedSms,
}: Props) {
    const fmt = useFormat();
    const formatDate = fmt.date;
    const formatDateTime = fmt.dateTime;
    const t = useT();
    const spacePath = useSpacePath();
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

            {/*
             * La jauge, en bandeau, avant tout le reste (T-257).
             *
             * « Où en est l'histoire, d'un coup d'œil » : c'est la question
             * qu'on se pose en ouvrant son espace, et elle n'avait de réponse
             * que sur la page « Le livre », là où l'on ne va qu'à la fin.
             *
             * Le décompte n'a **pas** de dénominateur en histoires — R-6
             * l'interdit, et « 8 sur 65 » ferait du fonds un stock à épuiser.
             * Le seul dénominateur affiché est celui que le contrat porte
             * déjà : les douze mois de collecte de R-2.
             */}
            <section
                aria-labelledby="gauge"
                className="card enter mt-8 p-6"
                style={stagger(2)}
            >
                <div className="flex flex-wrap items-baseline justify-between gap-x-6 gap-y-2">
                    <h2 id="gauge" className="eyebrow">
                        {t('initiator.dashboard.gauge_title')}
                    </h2>

                    {counts.endsAt !== null && (
                        <p className="text-brand-muted text-base">
                            {t('initiator.dashboard.until', {
                                date: formatDate(counts.endsAt),
                            })}
                        </p>
                    )}
                </div>

                <p className="text-brand mt-3 text-base">
                    {counts.asked === 0
                        ? t('initiator.dashboard.counts_none')
                        : [
                              counts.planned === null
                                  ? t('initiator.dashboard.counts_asked', {
                                        count: counts.asked,
                                    })
                                  : t('initiator.dashboard.counts_asked_of', {
                                        count: counts.asked,
                                        total: counts.planned,
                                    }),
                              t('initiator.dashboard.counts_recorded', {
                                  count: counts.recorded,
                              }),
                              t('initiator.dashboard.counts_shared', {
                                  count: counts.shared,
                              }),
                          ].join(' · ')}
                </p>

                <div className="mt-5">
                    <Gauge
                        columns={4}
                        words={readiness.words}
                        audioMinutes={readiness.audioMinutes}
                        pages={readiness.estimatedPages}
                        themes={readiness.themes}
                        minWords={readiness.thresholds.words}
                        minAudioMinutes={readiness.thresholds.audioMinutes}
                        minPages={readiness.thresholds.pages}
                        minThemes={readiness.thresholds.themes}
                    />
                </div>

                <p className="mt-5 flex flex-wrap items-center gap-x-4 gap-y-2 text-base">
                    <span className="text-brand-muted">
                        {readiness.ready
                            ? t('initiator.book.gauge.ready')
                            : t('initiator.book.gauge.not_ready')}
                    </span>

                    <Link
                        href={spacePath('/livre')}
                        className="text-brand font-medium underline-offset-4 hover:underline"
                    >
                        {t('initiator.dashboard.gauge_open')}
                    </Link>
                </p>
            </section>

            {/*
             * Deux colonnes à partir de `lg` (21 septembre 2026).
             *
             * L'espace tenait dans une colonne de 672 px à toutes les
             * largeurs : sur un écran de 1440, c'était 768 px de vide et des
             * lignes de texte à 960 px une fois la colonne élargie. La
             * largeur doit servir la **mise en page**, pas allonger les
             * lignes — une ligne de cent vingt signes ne se lit pas mieux
             * parce qu'elle tient.
             *
             * À gauche ce pour quoi on vient : la question en cours, puis où
             * en est chaque histoire. À droite ce qui s'y rapporte sans
             * presser — écouter comme un proche, demander une pause. L'ordre
             * du DOM reste celui de la lecture au téléphone, donc celui du
             * clavier et des lecteurs d'écran : la grille ne déplace rien,
             * elle range.
             */}
            <div className="lg:grid lg:grid-cols-[minmax(0,3fr)_minmax(0,2fr)] lg:items-start lg:gap-10">
                <div className="min-w-0">
                    <section
                        aria-labelledby="week"
                        className="card enter mt-8 p-6"
                        style={stagger(3)}
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
                            <p className="mt-4">
                                {t('initiator.copy_link.no_story')}
                            </p>
                        )}

                        <div className="mt-6 flex flex-col gap-4 sm:flex-row sm:items-center">
                            <button
                                type="button"
                                disabled={!hasCurrentStory}
                                onClick={() =>
                                    router.post(
                                        spacePath('/lien/question'),
                                        undefined,
                                        {
                                            preserveScroll: true,
                                        },
                                    )
                                }
                                className="btn-primary press flex-none disabled:opacity-60"
                            >
                                <Send />
                                {name === null
                                    ? t('initiator.dashboard.send_link_generic')
                                    : t('initiator.dashboard.send_link', {
                                          name,
                                      })}
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
                                copiedLabel={t(
                                    'initiator.dashboard.share.copied',
                                )}
                                whatsappLabel={t(
                                    'initiator.dashboard.share.whatsapp',
                                )}
                                smsLabel={t('initiator.dashboard.share.sms')}
                            />
                        )}
                    </section>
                </div>

                <div className="min-w-0 lg:mt-8">
                    {listensAsFamilyMember && (
                        <section
                            aria-labelledby="listen"
                            className="card enter mt-10 flex flex-col gap-4 p-6 sm:flex-row sm:items-center sm:justify-between lg:mt-0 lg:flex-col lg:items-start"
                            style={stagger(5)}
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

                    <p className="enter mt-10 text-base" style={stagger(6)}>
                        <Link
                            href="/espace/reglages"
                            className="text-brand-muted hover:text-brand inline-flex items-center gap-2 underline underline-offset-4 transition-colors"
                        >
                            <Pause className="size-4" />
                            {t('initiator.dashboard.pause')}
                        </Link>
                    </p>
                </div>
            </div>

            {/*
             * La frise prend toute la largeur (T-257).
             *
             * Elle vivait dans la colonne de gauche, à côté d'une colonne de
             * droite qui se terminait au deuxième écran : la moitié de la
             * page restait vide sur toute la hauteur du récit. Une frise se
             * lit en une colonne — deux couperaient le fil — mais rien ne
             * l'oblige à se serrer contre un vide.
             */}
            <section
                aria-labelledby="timeline"
                className="enter mt-10"
                style={stagger(4)}
            >
                <h2 id="timeline" className="eyebrow">
                    {t('initiator.dashboard.timeline')}
                </h2>

                <p className="text-brand-muted mt-3 text-base">
                    {t('initiator.dashboard.private_notice', {
                        name: name ?? '',
                    })}
                </p>

                {stories.length === 0 && upcoming.length === 0 ? (
                    <p className="card mt-5 p-5">
                        {t('initiator.dashboard.timeline_empty')}
                    </p>
                ) : (
                    <ol className="timeline-rail relative mt-5 flex flex-col gap-4 pl-9">
                        {stories.map((story) => {
                            const tone = toneFor(story.state);

                            return (
                                <li
                                    key={story.id}
                                    className="measure-free relative"
                                >
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
                                                        {
                                                            n: story.sequence,
                                                        },
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
                                                    spacePath(
                                                        `/histoires/${story.id}/photos/${id}`,
                                                    ),
                                                    {
                                                        preserveScroll: true,
                                                    },
                                                )
                                            }
                                        />

                                        <PhotoUploader
                                            compact
                                            action={spacePath(
                                                `/histoires/${story.id}/photos`,
                                            )}
                                        />
                                    </article>
                                </li>
                            );
                        })}

                        {/*
                         * Le repère d'aujourd'hui (T-257).
                         *
                         * La frise s'arrêtait au dernier récit, et
                         * rien ne disait que le fil continuait. Une
                         * ligne suffit : au-dessus ce qui est
                         * raconté, au-dessous ce qui attend, et le
                         * même rail traverse les deux.
                         */}
                        <li
                            className="measure-free relative py-1"
                            aria-hidden="true"
                        >
                            <span className="border-brand-accent bg-brand-background absolute top-1/2 -left-9 size-[1.375rem] -translate-y-1/2 rounded-full border-[3px]" />
                            <span className="eyebrow text-brand-muted">
                                {t('initiator.dashboard.today')}
                            </span>
                        </li>

                        {upcoming.map((entry) => (
                            <li
                                key={`${entry.kind}-${entry.id}`}
                                className="measure-free relative"
                            >
                                {/*
                                 * Un point creux : ce n'est pas
                                 * encore arrivé. La couleur pleine
                                 * reste pour ce qui existe.
                                 */}
                                <span
                                    aria-hidden="true"
                                    className="border-brand-sand bg-brand-background absolute top-5 -left-9 size-[1.375rem] rounded-full border-2"
                                />

                                <article className="card border-dashed p-5">
                                    <div className="flex flex-wrap items-start justify-between gap-3">
                                        <p className="text-brand-muted text-[0.8rem] font-semibold tracking-[0.08em] uppercase">
                                            {entry.sendAt === null
                                                ? '—'
                                                : formatDate(entry.sendAt)}
                                        </p>

                                        {entry.kind === 'story' ? (
                                            <Pill tone="gold">
                                                {t('initiator.dashboard.yours')}
                                            </Pill>
                                        ) : (
                                            entry.themeLabel !== null && (
                                                <Pill tone="muted">
                                                    {entry.themeLabel}
                                                </Pill>
                                            )
                                        )}
                                    </div>

                                    <p className="font-display text-brand mt-1 text-xl leading-snug font-medium">
                                        {entry.text}
                                    </p>

                                    {entry.photos > 0 && (
                                        <p className="text-brand-muted mt-2 text-base">
                                            {t('initiator.dashboard.photos', {
                                                count: entry.photos,
                                            })}
                                        </p>
                                    )}
                                </article>
                            </li>
                        ))}
                    </ol>
                )}

                <p className="mt-5 text-base">
                    <Link
                        href={spacePath('/questions')}
                        className="text-brand font-medium underline-offset-4 hover:underline"
                    >
                        {upcoming.length === 0
                            ? t('initiator.dashboard.upcoming_none')
                            : t('initiator.dashboard.upcoming_all')}
                    </Link>
                </p>
            </section>
        </>
    );
}
