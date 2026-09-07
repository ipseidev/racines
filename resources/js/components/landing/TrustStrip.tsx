import { SHELL } from '@/components/landing/primitives';
import { useT } from '@/hooks/useT';

const QUOTES = ['one', 'two', 'three'] as const;

/** Cinq étoiles pleines, dessinées : cinq caractères se rendent différemment d'une police à l'autre. */
function Stars({ label }: { label: string }) {
    return (
        <svg
            viewBox="0 0 100 18"
            role="img"
            aria-label={label}
            className="h-[1.15rem] w-auto text-[#F7F1E6]"
            fill="currentColor"
        >
            {[0, 20, 40, 60, 80].map((x) => (
                <path
                    key={x}
                    transform={`translate(${x} 0) scale(0.75)`}
                    d="M12 1l3.1 6.9L22 8.8l-5 4.8 1.3 7L12 17.3 5.7 20.6 7 13.6 2 8.8l6.9-.9L12 1Z"
                />
            ))}
        </svg>
    );
}

/**
 * S02 — Le bandeau sombre sous le héros.
 *
 * Trois appréciations, cinq étoiles chacune, celle du milieu plus grande :
 * c'est le rythme demandé. Aucun nom ni logo dessous — il n'y a rien à
 * attribuer.
 */
export default function TrustStrip() {
    const t = useT();
    const stars = t('public.lp.trust.stars');

    return (
        <section
            aria-labelledby="lp-trust"
            className="bg-brand-deep text-[#F7F1E6]"
        >
            <div className={`${SHELL} py-10 lg:py-12`}>
                <h2 id="lp-trust" className="sr-only">
                    {t('public.lp.trust.title')}
                </h2>

                <ul className="grid gap-9 sm:grid-cols-3 sm:items-center sm:gap-6">
                    {QUOTES.map((key) => (
                        <li
                            key={key}
                            className="flex flex-col items-center gap-3 text-center"
                        >
                            <Stars label={stars} />
                            {/*
                             * La couleur est explicite : `.font-display` pose
                             * `color: var(--color-brand)` dans sa propre règle,
                             * qui bat l'héritage du fond sombre — sans elle, le
                             * texte sortait vert foncé sur vert foncé.
                             */}
                            <p className="font-display text-[1.4rem] leading-tight font-medium text-[#F7F1E6] lg:text-[1.65rem]">
                                {t(`public.lp.trust.quotes.${key}`)}
                            </p>
                        </li>
                    ))}
                </ul>
            </div>
        </section>
    );
}
