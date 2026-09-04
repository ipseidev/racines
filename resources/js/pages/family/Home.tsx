import { Head, Link } from '@inertiajs/react';

import { useT } from '@/hooks/useT';

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

const longDate = (iso: string) =>
    new Intl.DateTimeFormat('fr-FR', { dateStyle: 'long' }).format(
        new Date(iso),
    );

/**
 * « Les histoires de {Prénom} ».
 *
 * La liste ne montre que ce que le narrateur a partagé. Le badge
 * « Nouvelle » veut dire « pas encore écoutée par **vous** » : une page
 * ouverte trois secondes n'est pas une écoute, et c'est ce que la chaîne H2
 * cherche à mesurer.
 */
export default function Home({
    narratorFirstName,
    inviterName,
    stories,
}: Props) {
    const t = useT();

    return (
        <>
            <Head
                title={
                    narratorFirstName === null
                        ? t('family.home.title_generic')
                        : t('family.home.title', {
                              first_name: narratorFirstName,
                          })
                }
            />

            <h1 className="font-display text-2xl leading-tight font-semibold sm:text-3xl">
                {narratorFirstName === null
                    ? t('family.home.title_generic')
                    : t('family.home.title', { first_name: narratorFirstName })}
            </h1>

            {stories.length === 0 ? (
                <p className="mt-8">{t('family.home.empty')}</p>
            ) : (
                <ul className="mt-8 flex flex-col gap-4">
                    {stories.map((story) => (
                        <li key={story.id}>
                            <Link
                                href={`${window.location.pathname}/stories/${story.id}`}
                                className="border-brand-sand bg-brand-surface block min-h-[2.75rem] rounded-md border px-4 py-4"
                            >
                                <span className="flex flex-wrap items-baseline gap-3">
                                    <span className="text-lg font-medium">
                                        {story.title ?? story.question}
                                    </span>
                                    {story.isNew ? (
                                        <span className="bg-brand-linen text-brand-text rounded-full px-3 py-1 text-sm">
                                            {t('family.home.new')}
                                        </span>
                                    ) : null}
                                </span>

                                <span className="text-brand-muted mt-1 block text-base">
                                    {[
                                        story.sharedAt === null
                                            ? null
                                            : longDate(story.sharedAt),
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
                                    ]
                                        .filter((one) => one !== null)
                                        .join(' · ')}
                                </span>

                                {story.yourReactions.length > 0 ? (
                                    <span className="mt-2 block text-base">
                                        {story.yourReactions
                                            .map((type) =>
                                                t(`family.reaction.${type}`),
                                            )
                                            .join(' · ')}
                                    </span>
                                ) : null}
                            </Link>
                        </li>
                    ))}
                </ul>
            )}

            <p className="text-brand-muted mt-12 text-base">
                {inviterName === null
                    ? t('family.home.footer_generic')
                    : t('family.home.footer', { inviter: inviterName })}
            </p>
        </>
    );
}
