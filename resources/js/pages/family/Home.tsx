import { Head, Link } from '@inertiajs/react';

import { Chevron, Headphones } from '@/components/space/Icons';
import { PageHeader } from '@/components/space/PageHeader';
import { Pill } from '@/components/space/Pill';
import { useT } from '@/hooks/useT';
import { formatDate } from '@/lib/dates';
import { stagger } from '@/lib/motion';

type Card = {
    id: string;
    title: string | null;
    question: string | null;
    sharedAt: string | null;
    durationSeconds: number | null;
    isNew: boolean;
    yourReactions: string[];
};

type Props = {
    narratorFirstName: string | null;
    inviterName: string | null;
    stories: Card[];
};

/**
 * « Les histoires de {Prénom} ».
 *
 * La liste ne montre que ce que le narrateur a partagé. Le badge
 * « Nouvelle » veut dire « pas encore écoutée par **vous** » : une page
 * ouverte trois secondes n'est pas une écoute, et c'est ce que la chaîne H2
 * cherche à mesurer.
 *
 * Chaque histoire est une carte pleine hauteur plutôt qu'une ligne de liste
 * (T-158) : sur un téléphone tenu d'une main, la cible se cherche du pouce, et
 * une carte qui cède sous le doigt dit qu'elle a été touchée avant même que la
 * page suivante arrive.
 */
export default function Home({
    narratorFirstName,
    inviterName,
    stories,
}: Props) {
    const t = useT();

    const title =
        narratorFirstName === null
            ? t('family.home.title_generic')
            : t('family.home.title', { first_name: narratorFirstName });

    return (
        <>
            <Head title={title} />

            <PageHeader
                eyebrow={t('family.home.eyebrow')}
                title={title}
                intro={
                    stories.length === 0
                        ? t('family.home.intro_empty')
                        : t('family.home.intro')
                }
            />

            {stories.length === 0 ? (
                <p className="card enter mt-8 px-5 py-6" style={stagger(1)}>
                    {t('family.home.empty')}
                </p>
            ) : (
                <ul className="mt-8 flex flex-col gap-3">
                    {stories.map((story, index) => (
                        <li
                            key={story.id}
                            className="enter"
                            style={stagger(index + 1)}
                        >
                            <Link
                                href={`${window.location.pathname}/stories/${story.id}`}
                                className="card press hover:border-brand/35 flex items-center gap-4 px-5 py-4 no-underline"
                            >
                                {/*
                                 * Le casque dit « ceci s'écoute » sans un mot,
                                 * et il tient la colonne de gauche pour que
                                 * l'œil descende la liste par ses titres.
                                 */}
                                <span
                                    aria-hidden="true"
                                    className="bg-brand-linen text-brand flex size-11 flex-none items-center justify-center rounded-full"
                                >
                                    <Headphones className="size-5" />
                                </span>

                                <span className="min-w-0 flex-1">
                                    <span className="flex flex-wrap items-center gap-x-3 gap-y-2">
                                        <span className="font-display text-[1.1875rem] leading-snug font-semibold">
                                            {story.title ?? story.question}
                                        </span>
                                        {story.isNew ? (
                                            <Pill tone="gold">
                                                {t('family.home.new')}
                                            </Pill>
                                        ) : null}
                                    </span>

                                    <span className="text-brand-muted mt-1 block text-[0.9375rem]">
                                        {[
                                            story.sharedAt === null
                                                ? null
                                                : formatDate(story.sharedAt),
                                            story.durationSeconds === null
                                                ? null
                                                : t('family.home.duration', {
                                                      minutes: String(
                                                          Math.max(
                                                              1,
                                                              Math.round(
                                                                  story.durationSeconds /
                                                                      60,
                                                              ),
                                                          ),
                                                      ),
                                                  }),
                                            story.yourReactions.length > 0
                                                ? t(
                                                      'family.home.reacted_by_you',
                                                  )
                                                : null,
                                        ]
                                            .filter((one) => one !== null)
                                            .join(' · ')}
                                    </span>
                                </span>

                                <Chevron
                                    aria-hidden="true"
                                    className="text-brand-sand size-5 flex-none -rotate-90"
                                />
                            </Link>
                        </li>
                    ))}
                </ul>
            )}

            <p
                className="text-brand-muted enter mt-12 text-[0.9375rem]"
                style={stagger(stories.length + 2)}
            >
                {inviterName === null
                    ? t('family.home.footer_generic')
                    : t('family.home.footer', { inviter: inviterName })}
            </p>
        </>
    );
}
