import { useCallback, useEffect, useRef, useState } from 'react';

import { BAND, Section, SHELL } from '@/components/landing/primitives';
import { useT } from '@/hooks/useT';

const ITEMS = [
    'one',
    'two',
    'three',
    'four',
    'five',
    'six',
    'seven',
    'eight',
    'nine',
] as const;

/** Le défilement automatique : assez lent pour qu'on ait le temps de lire trois avis. */
const EVERY = 6_000;

const REDUCED = '(prefers-reduced-motion: reduce)';

/** Cinq étoiles pleines, dessinées : cinq caractères se rendent différemment d'une police à l'autre. */
function Stars({ label }: { label: string }) {
    return (
        <svg
            viewBox="0 0 100 18"
            role="img"
            aria-label={label}
            className="text-brand-accent mx-auto h-4 w-auto"
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

function Arrow({
    label,
    forward,
    onClick,
    className,
}: {
    label: string;
    forward: boolean;
    onClick: () => void;
    className: string;
}) {
    return (
        <button
            type="button"
            onClick={onClick}
            aria-label={label}
            /*
             * Aucune classe d'affichage en base : `flex` ici entrait en
             * conflit avec le `hidden` de l'appelant — deux utilitaires
             * d'affichage, et c'est l'ordre de la feuille de styles qui
             * tranche, pas celui de l'attribut. Les flèches de bureau
             * restaient affichées sur téléphone, en absolu, et débordaient de
             * la page. L'appelant décide.
             */
            className={`bg-brand text-brand-foreground hover:bg-brand-deep size-11 items-center justify-center rounded-full transition-colors ${className}`}
        >
            <svg
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                strokeWidth="1.8"
                strokeLinecap="round"
                aria-hidden="true"
                className="size-5"
            >
                <path
                    d={
                        forward
                            ? 'M5 12h14m0 0-5-5m5 5-5 5'
                            : 'M19 12H5m0 0 5-5m-5 5 5 5'
                    }
                />
            </svg>
        </button>
    );
}

/**
 * S10 — Les avis, en carrousel.
 *
 * Un rail qui défile avec accrochage (`scroll-snap`) plutôt qu'une piste
 * translatée : le glissement au doigt, la molette et la tabulation
 * fonctionnent sans une ligne de code, et un avis qui prend le focus au
 * clavier est amené à l'écran par le navigateur lui-même.
 *
 * Le défilement automatique s'arrête dans trois cas, et chacun compte : quand
 * la souris survole le bloc, quand le focus y entre — sinon on lit un avis qui
 * s'en va —, et quand le visiteur a demandé moins de mouvement. Dans ce
 * dernier cas il ne démarre jamais : `prefers-reduced-motion` n'est pas une
 * préférence esthétique, c'est un besoin.
 */
export default function Testimonials() {
    const t = useT();
    const rail = useRef<HTMLUListElement>(null);
    const [page, setPage] = useState(0);
    const [pages, setPages] = useState(1);
    const [paused, setPaused] = useState(false);

    /*
     * La géométrie du rail : le pas d'une page, et combien il y en a.
     *
     * Mesurée sur les cartes, pas sur un rapport de largeurs :
     * `scrollWidth / clientWidth` donnait 9,55 sur un téléphone qui affiche une
     * carte à la fois, donc dix traits de position pour neuf avis, et la rangée
     * de traits débordait de l'écran. Le pas entre deux cartes dit tout :
     * combien tiennent dans la vue, et où commence chaque page.
     */
    const geometry = useCallback(() => {
        const element = rail.current;

        if (element === null || element.children.length === 0) {
            return null;
        }

        const cards = [...element.children] as HTMLElement[];
        const step =
            cards.length > 1
                ? cards[1].offsetLeft - cards[0].offsetLeft
                : element.clientWidth;

        if (step <= 0) {
            return null;
        }

        const perPage = Math.max(1, Math.round(element.clientWidth / step));

        return {
            element,
            stride: step * perPage,
            pages: Math.ceil(cards.length / perPage),
        };
    }, []);

    const measure = useCallback(() => {
        const found = geometry();

        if (found === null) {
            return;
        }

        setPages(found.pages);
        setPage(
            Math.min(
                found.pages - 1,
                Math.round(found.element.scrollLeft / found.stride),
            ),
        );
    }, [geometry]);

    useEffect(() => {
        measure();

        const element = rail.current;

        if (element === null) {
            return;
        }

        const observer = new ResizeObserver(measure);
        observer.observe(element);
        element.addEventListener('scroll', measure, { passive: true });

        return () => {
            observer.disconnect();
            element.removeEventListener('scroll', measure);
        };
    }, [measure]);

    const goTo = useCallback(
        (index: number) => {
            const found = geometry();

            if (found === null) {
                return;
            }

            const target = ((index % found.pages) + found.pages) % found.pages;

            found.element.scrollTo({
                left: found.stride * target,
                behavior: window.matchMedia(REDUCED).matches
                    ? 'auto'
                    : 'smooth',
            });
        },
        [geometry],
    );

    useEffect(() => {
        if (paused || window.matchMedia(REDUCED).matches) {
            return;
        }

        const timer = window.setInterval(() => {
            const found = geometry();

            if (found === null) {
                return;
            }

            goTo(Math.round(found.element.scrollLeft / found.stride) + 1);
        }, EVERY);

        return () => window.clearInterval(timer);
    }, [geometry, goTo, paused]);

    const stars = t('public.lp.testimonials.stars');

    return (
        <Section tone="white" labelledBy="lp-testimonials" className={BAND}>
            <div
                className={SHELL}
                onMouseEnter={() => setPaused(true)}
                onMouseLeave={() => setPaused(false)}
                onFocusCapture={() => setPaused(true)}
                onBlurCapture={() => setPaused(false)}
            >
                <h2 id="lp-testimonials" className="sr-only">
                    {t('public.lp.testimonials.title')}
                </h2>

                {/*
                 * Le rembourrage est sur le conteneur, pas sur le rail : dans
                 * un rail qui défile, la zone de rembourrage reste **visible**,
                 * et la quatrième carte dépassait à droite. Ici les flèches
                 * vivent dans la gouttière, hors du rail.
                 */}
                <div className="relative lg:px-14">
                    <ul
                        ref={rail}
                        className="flex snap-x snap-mandatory [scrollbar-width:none] gap-6 overflow-x-auto pb-2 [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden"
                    >
                        {ITEMS.map((key) => (
                            <li
                                key={key}
                                className="flex w-full flex-none snap-start flex-col items-center gap-4 text-center sm:w-[calc((100%-1.5rem)/2)] lg:w-[calc((100%-3rem)/3)]"
                            >
                                <Stars label={stars} />
                                <h3 className="font-display text-brand text-[1.45rem] leading-tight font-medium">
                                    {t(
                                        `public.lp.testimonials.items.${key}.title`,
                                    )}
                                </h3>
                                <blockquote className="text-brand-muted max-w-[26em] text-[1rem] leading-relaxed">
                                    «&nbsp;
                                    {t(
                                        `public.lp.testimonials.items.${key}.quote`,
                                    )}
                                    &nbsp;»
                                </blockquote>
                                <cite className="text-brand mt-auto text-[0.95rem] font-semibold not-italic">
                                    {t(
                                        `public.lp.testimonials.items.${key}.author`,
                                    )}
                                </cite>
                            </li>
                        ))}
                    </ul>

                    {/* Les flèches, dans la gouttière dès qu'il y a la place. */}
                    <Arrow
                        label={t('public.lp.testimonials.previous')}
                        forward={false}
                        onClick={() => goTo(page - 1)}
                        className="absolute top-1/2 left-0 hidden -translate-y-1/2 lg:flex"
                    />
                    <Arrow
                        label={t('public.lp.testimonials.next')}
                        forward
                        onClick={() => goTo(page + 1)}
                        className="absolute top-1/2 right-0 hidden -translate-y-1/2 lg:flex"
                    />
                </div>

                <div className="mt-7 flex items-center justify-center gap-5 lg:gap-3">
                    <Arrow
                        label={t('public.lp.testimonials.previous')}
                        forward={false}
                        onClick={() => goTo(page - 1)}
                        className="flex lg:hidden"
                    />

                    {/* Les traits de position : de vrais boutons, pas des puces décoratives. */}
                    <div className="flex items-center gap-1.5 lg:gap-2">
                        {Array.from({ length: pages }, (_, index) => (
                            <button
                                key={index}
                                type="button"
                                onClick={() => goTo(index)}
                                aria-label={t('public.lp.testimonials.page', {
                                    number: index + 1,
                                })}
                                aria-current={index === page}
                                className={`h-1.5 w-4 rounded-full transition-colors lg:w-8 ${
                                    index === page
                                        ? 'bg-brand'
                                        : 'bg-brand-sand hover:bg-brand-muted'
                                }`}
                            />
                        ))}
                    </div>

                    <Arrow
                        label={t('public.lp.testimonials.next')}
                        forward
                        onClick={() => goTo(page + 1)}
                        className="flex lg:hidden"
                    />
                </div>
            </div>
        </Section>
    );
}
