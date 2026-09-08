import { TryLink } from '@/components/landing/primitives';
import { useT } from '@/hooks/useT';
import { photo } from '@/lib/photo';

/*
 * Les trois tuiles, chacune avec son pictogramme : le crayon qu'on ne prend
 * pas, l'application qu'on n'installe pas, le mot de passe qu'on n'a pas à
 * retenir.
 */
const MARKS = [
    { key: 'no_writing', icon: 'M4 20h4L19 9l-4-4L4 16v4Zm11-15 4 4' },
    { key: 'no_app', icon: 'M12 4v11m0 0-4-4m4 4 4-4M5 19h14' },
    {
        key: 'no_password',
        icon: 'M14 8a3 3 0 1 1 3 3M14 8 4 18v2h3v-2h2v-2h2l3-3m2-1a3 3 0 0 1-3-3',
    },
] as const;

/**
 * S12 — La simplicité, pour la personne qui raconte.
 *
 * Une carte pleine largeur coupée en deux : la photo d'un côté, le panneau
 * sombre de l'autre. C'est la forme du bloc de l'accueil, et elle est reprise
 * telle quelle — l'objection qu'elle lève est la seule qui compte vraiment,
 * « est-ce que ma mère va y arriver ? », et l'essai y répond en soixante
 * secondes.
 */
export default function EasyForStorytellers({ variant }: { variant: string }) {
    const t = useT();

    return (
        <section
            aria-labelledby="lp-easy"
            className="scroll-mt-6 py-11 sm:py-14 lg:py-20"
        >
            {/*
             * Plus large que les autres sections : le conteneur commun
             * (1 180 px) serrait la photo, et c'est le seul bloc de la page
             * dont l'image porte autant que le texte.
             */}
            <div className="mx-auto w-full max-w-[86rem] px-5 sm:px-8 lg:px-10">
                <div className="grid w-full overflow-hidden rounded-2xl lg:grid-cols-2">
                    {/*
                     * La largeur native est passée à `photo()` : 1 448 et non
                     * 1 400. Annoncer un barreau qu'on ne peut pas servir
                     * ferait charger un fichier plus petit que l'emplacement.
                     */}
                    <img
                        {...photo('cadeau', 1448)}
                        sizes="(min-width: 1024px) 42rem, 100vw"
                        alt={t('public.lp.easy.photo_alt')}
                        width="1448"
                        height="1086"
                        loading="lazy"
                        className="aspect-[4/3] h-full w-full object-cover lg:aspect-auto"
                    />

                    <div className="bg-brand-deep flex flex-col gap-6 px-7 py-11 lg:px-12 lg:py-14">
                        <h2
                            id="lp-easy"
                            className="font-display text-[1.75rem] leading-[1.12] font-medium text-[#F7F1E6] sm:text-[2.1rem] lg:text-[2.4rem]"
                        >
                            {t('public.lp.easy.title')}
                        </h2>

                        <p className="text-[1.0625rem] text-[#C9C0B2] sm:text-[1.15rem]">
                            {t('public.lp.easy.lede')}
                        </p>

                        <ul className="grid grid-cols-3 gap-2.5">
                            {MARKS.map((mark) => (
                                <li
                                    key={mark.key}
                                    className="flex flex-col items-center gap-2.5 rounded-md bg-white/8 px-3 py-5 text-center"
                                >
                                    <svg
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        strokeWidth="1.6"
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        aria-hidden="true"
                                        className="size-6 text-[#F7F1E6]"
                                    >
                                        <path d={mark.icon} />
                                    </svg>
                                    <span className="text-[0.95rem] leading-snug font-medium text-[#F7F1E6] sm:text-[1.05rem]">
                                        {t(`public.lp.easy.marks.${mark.key}`)}
                                    </span>
                                </li>
                            ))}
                        </ul>

                        <TryLink
                            section="easy"
                            variant={variant}
                            className="bg-brand-surface text-brand hover:bg-brand-linen flex min-h-[3.5rem] w-full items-center justify-center gap-3 rounded-md text-[1.05rem] font-semibold transition-colors"
                        >
                            <span className="bg-brand-accent size-2.5 flex-none rounded-full" />
                            {t('public.lp.easy.cta')}
                        </TryLink>
                    </div>
                </div>
            </div>
        </section>
    );
}
