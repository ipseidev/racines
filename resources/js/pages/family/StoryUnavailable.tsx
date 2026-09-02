import { Head, Link } from '@inertiajs/react';

import { useT } from '@/hooks/useT';

type Props = {
    backUrl: string | null;
};

/**
 * « Cette histoire n'est pas disponible. »
 *
 * Et rien d'autre : ni pourquoi, ni depuis quand, ni de quelle histoire il
 * s'agissait. Un proche qui apprendrait qu'une histoire existe mais lui est
 * refusée en saurait déjà trop — et le narrateur n'a pas à justifier ses
 * retraits auprès de sa famille.
 */
export default function StoryUnavailable({ backUrl }: Props) {
    const t = useT();

    return (
        <>
            <Head title={t('family.story_unavailable.title')} />

            <h1 className="font-display text-2xl leading-tight font-semibold sm:text-3xl">
                {t('family.story_unavailable.title')}
            </h1>

            <p className="mt-6">{t('family.story_unavailable.body')}</p>

            {backUrl === null ? null : (
                <Link
                    href={backUrl}
                    className="border-brand-muted/40 mt-8 inline-block min-h-[2.75rem] rounded-md border px-6 py-3 text-lg"
                >
                    {t('family.story.back')}
                </Link>
            )}
        </>
    );
}
