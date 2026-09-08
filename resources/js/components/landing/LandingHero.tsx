import { useEffect, useRef } from 'react';

import { useBrand } from '@/brand/BrandProvider';
import { BuyButton, SHELL } from '@/components/landing/primitives';

import { SECONDARY } from '@/components/marketing/styles';
import { useT } from '@/hooks/useT';
import { photo } from '@/lib/photo';

const CHECKS = ['voice', 'no_app', 'digital', 'no_writing'] as const;

/** Une coche à l'échelle de ces lignes-là : celle de `marketing/Check` est fixée à 22 px. */
function Tick() {
    return (
        <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="2.4"
            aria-hidden="true"
            className="text-brand mt-[0.15em] size-4 flex-none"
        >
            <path d="m5 12.5 4.5 4.5L19 7" />
        </svg>
    );
}

/**
 * S01 — Le héros de la variante.
 *
 * L'ordre de lecture change avec l'écran, et ce n'est pas un caprice : sur
 * téléphone le bouton d'achat doit être **avant** le visuel, sinon la
 * première décision de la page se prend après un défilement. Sur bureau, le
 * texte tient la colonne large et la photo la seconde, alignée en haut.
 *
 * La photo montre les deux générations **et** le livre : un recadrage qui
 * perd le produit fait un joli héros qui ne vend rien. La vignette posée
 * dessus rappelle l'objet, et ne porte aucune note ni aucun avis — nous n'en
 * avons pas.
 */
export default function LandingHero({
    variant,
    price,
}: {
    variant: string;
    price: number;
}) {
    const t = useT();
    const brand = useBrand();
    const video = useRef<HTMLVideoElement>(null);

    // La lecture est lancée à la main plutôt que par l'attribut `autoplay` :
    // c'est le seul moyen de ne **pas** la lancer quand le visiteur a demandé
    // moins de mouvement, et un navigateur qui refuse la lecture ne laisse
    // alors qu'une promesse rejetée à ignorer.
    useEffect(() => {
        const element = video.current;

        if (
            element === null ||
            window.matchMedia('(prefers-reduced-motion: reduce)').matches
        ) {
            return;
        }

        void element.play().catch(() => undefined);
    }, []);

    return (
        <section
            id="haut"
            className={`${SHELL} grid gap-8 pt-8 pb-12 lg:grid-cols-2 lg:grid-rows-[auto_auto] lg:items-start lg:gap-x-12 lg:gap-y-8 lg:pt-14 lg:pb-20`}
        >
            <div className="order-1 flex flex-col gap-6 lg:col-start-1 lg:row-start-1">
                <h1 className="font-display text-[1.85rem] leading-[1.1] font-medium sm:text-[2.35rem] lg:text-[2.85rem]">
                    {t('public.lp.hero.title')}
                </h1>

                <p className="text-brand-muted max-w-[36em] text-[1rem] leading-relaxed sm:text-[1.0625rem]">
                    {t('public.lp.hero.lede', { brand: brand.name })}
                </p>

                <div className="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
                    <BuyButton
                        section="hero"
                        variant={variant}
                        price={price}
                        withPrice
                        className="w-full sm:w-auto"
                    />
                    <a
                        href="#comment-ca-marche"
                        className={`${SECONDARY} w-full sm:w-auto`}
                    >
                        {t('public.lp.cta.how')}
                    </a>
                </div>
            </div>

            <div className="order-2 lg:col-start-2 lg:row-span-2 lg:row-start-1">
                <div className="relative">
                    {/*
                     * La vidéo du héros, muette et en boucle.
                     *
                     * `muted` et `playsInline` ne sont pas décoratifs : sans
                     * eux, iOS refuse la lecture automatique et n'affiche que
                     * l'image d'attente. Deux sources, parce que le MP4 est lu
                     * partout et le WebM plus léger là où il est accepté.
                     *
                     * Elle s'immobilise pour qui a demandé moins de mouvement
                     * — `prefers-reduced-motion` n'est pas une préférence
                     * esthétique — et l'image d'attente prend alors sa place.
                     */}
                    {/* eslint-disable-next-line jsx-a11y/media-has-caption -- vidéo muette et décorative, le texte du héros dit tout */}
                    <video
                        ref={video}
                        poster="/img/landing/hero-video-poster.webp"
                        width="1280"
                        height="720"
                        muted
                        loop
                        playsInline
                        preload="metadata"
                        aria-label={t('public.landing.hero.photo_alt')}
                        className="bg-brand-linen aspect-square w-full rounded-2xl object-cover"
                    >
                        <source
                            src="/video/landing/hero.webm"
                            type="video/webm"
                        />
                        <source
                            src="/video/landing/hero.mp4"
                            type="video/mp4"
                        />
                    </video>

                    <div className="border-brand-sand bg-brand-surface absolute -bottom-5 left-4 flex items-center gap-3 rounded-lg border px-4 py-3 shadow-[0_10px_30px_rgba(38,33,28,0.14)] sm:left-6">
                        <img
                            {...photo('livre')}
                            sizes="72px"
                            alt=""
                            width="1400"
                            height="1050"
                            loading="lazy"
                            className="size-14 flex-none rounded-sm object-cover"
                        />
                        <span className="flex flex-col leading-tight">
                            <span className="font-display text-brand text-[1.05rem] font-medium">
                                {t('public.lp.hero.thumb.label')}
                            </span>
                            <span className="text-brand-muted text-[0.85rem]">
                                {t('public.lp.hero.thumb.body')}
                            </span>
                        </span>
                    </div>
                </div>
            </div>

            <div className="order-3 flex flex-col gap-5 pt-6 lg:col-start-1 lg:row-start-2 lg:pt-0">
                <ul className="flex flex-col gap-2">
                    {CHECKS.map((key) => (
                        <li
                            key={key}
                            className="text-brand-muted flex gap-2 text-[0.9rem] leading-snug"
                        >
                            <Tick />
                            <span>{t(`public.lp.hero.checks.${key}`)}</span>
                        </li>
                    ))}
                </ul>
            </div>
        </section>
    );
}
