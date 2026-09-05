import { Head, Link } from '@inertiajs/react';

import { Chevron } from '@/components/space/Icons';
import { useT } from '@/hooks/useT';
import { stagger } from '@/lib/motion';

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

            <div className="card enter px-6 py-8">
                <h1 className="font-display text-[1.75rem] leading-tight font-semibold sm:text-[2rem]">
                    {t('family.story_unavailable.title')}
                </h1>

                <p className="text-brand-muted mt-4 text-[1.0625rem]">
                    {t('family.story_unavailable.body')}
                </p>

                {backUrl === null ? null : (
                    <Link
                        href={backUrl}
                        className="btn-secondary press mt-7"
                        style={stagger(1)}
                    >
                        <Chevron
                            aria-hidden="true"
                            className="size-4 rotate-90"
                        />
                        {t('family.story.back')}
                    </Link>
                )}
            </div>
        </>
    );
}
