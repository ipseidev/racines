import { Link } from '@inertiajs/react';
import { useState } from 'react';

import { useBrand } from '@/brand/BrandProvider';
import { BAND, Section, SHELL } from '@/components/landing/primitives';
import SampleAudio from '@/components/landing/SampleAudio';
import { track } from '@/components/landing/track';
import { formatPrice } from '@/hooks/usePilot';
import { useT } from '@/hooks/useT';
import { photo } from '@/lib/photo';

type Sample = { src: string; disclosed: boolean };

/*
 * Les cinq vues de la galerie, dans l'ordre de la bande de vignettes.
 *
 * Aucune n'est une double page ni un détail de page avec son QR code : ces
 * deux photographies n'existent pas, le livre n'étant pas imprimé. On montre
 * donc cinq vues **différentes** de ce qu'on a, plutôt que cinq recadrages du
 * même fichier — une bande de vignettes qui répète la même image fait croire
 * à cinq angles.
 *
 * `alt` est lu dans le catalogue de l'accueil : ce sont les mêmes fichiers, et
 * une description qui divergerait décrirait une autre photo.
 */
const VIEWS = [
    { name: 'livre', label: 'closed', alt: 'public.landing.book.photo_alt' },
    { name: 'etape-4', label: 'phone', alt: 'public.landing.how.four.alt' },
    { name: 'etape-3', label: 'held', alt: 'public.landing.how.three.alt' },
    { name: 'etape-1', label: 'photos', alt: 'public.landing.how.one.alt' },
    { name: 'hero', label: 'family', alt: 'public.landing.hero.photo_alt' },
] as const;

const BENEFITS = [
    {
        key: 'read',
        icon: 'M12 6.5v13M12 6.5A4 4 0 0 0 4 6.5v11a4 4 0 0 1 8-2m0-9a4 4 0 0 1 8 0v11a4 4 0 0 0-8-2',
    },
    {
        key: 'hear',
        icon: 'M4 4h6v6H4V4Zm10 0h6v6h-6V4ZM4 14h6v6H4v-6Zm10 3h3m0 0v3m3-3v3',
    },
    { key: 'bound', icon: 'M7 3h10v18l-5-4-5 4V3Z' },
] as const;

const INCLUDES = [
    'questions',
    'book',
    'device',
    'qr',
    'download',
    'family',
] as const;

const GUARANTEES = [
    { key: 'refund', icon: 'M12 3l7 3v6c0 4-3 7-7 9-4-2-7-5-7-9V6l7-3Z' },
    {
        key: 'yours',
        icon: 'M7 9a3 3 0 0 0 0 6c2 0 3-3 5-3s3 3 5 3a3 3 0 0 0 0-6c-2 0-3 3-5 3s-3-3-5-3Z',
    },
    { key: 'download', icon: 'M12 4v11m0 0-4-4m4 4 4-4M5 19h14' },
] as const;

/** Cinq étoiles pleines, dessinées : cinq caractères se rendent différemment d'une police à l'autre. */
function Stars({ label }: { label: string }) {
    return (
        <svg
            viewBox="0 0 100 18"
            role="img"
            aria-label={label}
            className="text-brand-accent h-4 w-auto"
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

function Icon({ path, className }: { path: string; className: string }) {
    return (
        <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="1.7"
            strokeLinecap="round"
            strokeLinejoin="round"
            aria-hidden="true"
            className={className}
        >
            <path d={path} />
        </svg>
    );
}

/**
 * S07 — La fiche produit.
 *
 * La structure est celle du leader, relevée sur sa page produit : à gauche une
 * bande de vignettes verticale, la grande vue et la carte d'écoute ; à droite
 * le bandeau de notoriété, le titre, les trois bénéfices, ce que comprend
 * l'achat en deux colonnes, le bouton pleine largeur et les trois
 * réassurances.
 *
 * Trois blocs seulement, et c'est ce qui permet à l'ordre de changer avec
 * l'écran sans dupliquer un élément focusable : sur téléphone, le titre, puis
 * la galerie, puis le reste de l'offre ; sur bureau, la galerie tient la
 * colonne de gauche sur deux lignes.
 */
export default function ProductOffer({
    variant,
    price,
    sample,
}: {
    variant: string;
    price: number;
    sample: Sample | null;
}) {
    const t = useT();
    const brand = useBrand();
    const [view, setView] = useState(0);
    const current = VIEWS[view];

    return (
        <Section
            id="le-livre"
            tone="white"
            labelledBy="lp-offer"
            className={BAND}
        >
            <div
                className={`${SHELL} grid gap-8 lg:grid-cols-[56fr_44fr] lg:grid-rows-[auto_1fr] lg:items-start lg:gap-x-12`}
            >
                {/* Le bandeau de notoriété, le titre, le chapeau. */}
                <div className="order-1 flex flex-col gap-4 lg:col-start-2 lg:row-start-1">
                    <div className="flex flex-wrap items-center gap-x-4 gap-y-2">
                        <span className="bg-brand/10 text-brand inline-flex items-center gap-2 rounded-full px-3.5 py-1.5 text-[0.72rem] font-semibold tracking-[0.1em] uppercase">
                            <span className="bg-brand size-1.5 rounded-full" />
                            {t('public.lp.offer.badge')}
                        </span>
                        <span className="flex items-center gap-2">
                            <Stars label={t('public.lp.offer.stars')} />
                            <span className="text-brand-muted text-[0.95rem] font-medium">
                                {t('public.lp.offer.rating')}
                            </span>
                        </span>
                    </div>

                    <h2
                        id="lp-offer"
                        className="font-display text-[1.9rem] leading-[1.1] font-medium sm:text-[2.35rem] lg:text-[2.6rem]"
                    >
                        {t('public.lp.offer.title')}
                    </h2>

                    <p className="text-brand-text max-w-[34em] text-[0.95rem] leading-relaxed sm:text-[1rem]">
                        {t('public.lp.offer.lede')}
                    </p>
                </div>

                {/* La galerie et la carte d'écoute : la colonne de gauche. */}
                <div className="order-2 flex min-w-0 flex-col gap-4 lg:col-start-1 lg:row-span-2 lg:row-start-1">
                    <div className="flex gap-3">
                        {/*
                         * La bande de vignettes : de vrais boutons, donc
                         * atteignables au clavier, et aucun défilement
                         * automatique. Chacune dit ce qu'elle montre — « Voir :
                         * le livre tenu en main » — plutôt que « image 2 ».
                         */}
                        <div
                            role="group"
                            aria-label={t('public.lp.offer.gallery.aria')}
                            className="flex flex-none flex-col gap-2.5"
                        >
                            {VIEWS.map((item, index) => (
                                <button
                                    key={item.name}
                                    type="button"
                                    onClick={() => setView(index)}
                                    aria-pressed={index === view}
                                    aria-label={t(
                                        'public.lp.offer.gallery.thumb',
                                        {
                                            label: t(
                                                `public.lp.offer.gallery.${item.label}`,
                                            ),
                                        },
                                    )}
                                    className={`overflow-hidden rounded-md border-2 transition-colors ${
                                        index === view
                                            ? 'border-brand'
                                            : 'border-brand-sand hover:border-brand-muted'
                                    }`}
                                >
                                    <img
                                        {...photo(item.name)}
                                        sizes="72px"
                                        alt=""
                                        width="1400"
                                        height="1050"
                                        loading="lazy"
                                        className="aspect-square w-12 object-cover sm:w-16"
                                    />
                                </button>
                            ))}
                        </div>

                        <div className="relative min-w-0 flex-1">
                            <img
                                key={current.name}
                                {...photo(current.name)}
                                sizes="(min-width: 1024px) 36rem, 100vw"
                                alt={t(current.alt)}
                                width="1400"
                                height="1050"
                                loading="lazy"
                                className="aspect-square w-full rounded-xl object-cover"
                            />

                            {/* La vue suivante, comme chez le leader : un seul sens, et aucun mouvement tout seul. */}
                            <button
                                type="button"
                                onClick={() =>
                                    setView((index) =>
                                        index + 1 === VIEWS.length
                                            ? 0
                                            : index + 1,
                                    )
                                }
                                aria-label={t('public.lp.offer.gallery.next')}
                                className="bg-brand-surface/90 text-brand hover:bg-brand-surface absolute top-1/2 right-3 flex size-11 -translate-y-1/2 items-center justify-center rounded-full shadow-[0_6px_18px_rgba(38,33,28,0.18)] transition-colors"
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
                                    <path d="M5 12h14m0 0-5-5m5 5-5 5" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    {/*
                     * La carte d'écoute, sous la galerie, et l'ancre que toutes
                     * les autres sections visent. `min-w-0` n'est pas
                     * décoratif : un élément de grille garde `min-width: auto`,
                     * donc la colonne était forcée à la largeur minimale de son
                     * contenu — les barres de la frise —, et la page débordait à
                     * l'horizontale sur un téléphone.
                     */}
                    <figure
                        id="ecouter"
                        className="border-brand-sand bg-brand-surface flex min-w-0 scroll-mt-6 flex-col gap-4 rounded-xl border p-5"
                    >
                        <figcaption className="flex items-center gap-4">
                            {/*
                             * Un pictogramme, pas un faux code : aucune
                             * destination imprimée n'est vérifiée, et un carré
                             * de pixels crédible ferait scanner dans le vide.
                             * Le bouton de lecture fait le travail.
                             */}
                            <Icon
                                path="M4 4h6v6H4V4Zm10 0h6v6h-6V4ZM4 14h6v6H4v-6Zm10 3h3m0 0v3m3-3v3"
                                className="border-brand-sand text-brand size-12 flex-none rounded-md border p-2"
                            />
                            <span className="flex min-w-0 flex-col gap-0.5">
                                <span className="text-brand-muted text-[0.72rem] font-semibold tracking-[0.12em] uppercase">
                                    {t('public.lp.offer.player.label')}
                                </span>
                                <span className="font-display text-brand text-[1.15rem] leading-tight font-medium italic">
                                    {t('public.lp.offer.player.title')}
                                </span>
                                <span className="text-brand-muted text-[0.85rem]">
                                    {t('public.lp.offer.player.attribution', {
                                        brand: brand.name,
                                    })}
                                </span>
                            </span>
                        </figcaption>

                        <SampleAudio sample={sample} variant={variant} />
                    </figure>
                </div>

                {/* Les bénéfices, ce que comprend l'achat, le bouton, les réassurances. */}
                <div className="order-3 flex flex-col gap-6 lg:col-start-2 lg:row-start-2">
                    <dl className="border-brand-sand flex flex-col gap-5 border-t pt-6">
                        {BENEFITS.map((item) => (
                            <div key={item.key} className="flex gap-4">
                                <span className="bg-brand-gold/25 text-brand flex size-11 flex-none items-center justify-center rounded-full">
                                    <Icon path={item.icon} className="size-5" />
                                </span>
                                <div className="flex flex-col gap-1">
                                    <dt className="font-display text-brand text-[1.35rem] leading-tight font-medium">
                                        {t(`public.lp.offer.${item.key}.title`)}
                                    </dt>
                                    <dd className="text-brand-text text-[0.95rem] leading-relaxed">
                                        {t(`public.lp.offer.${item.key}.body`, {
                                            brand: brand.name,
                                        })}
                                    </dd>
                                </div>
                            </div>
                        ))}
                    </dl>

                    <ul className="border-brand-sand grid gap-x-8 gap-y-2.5 border-t pt-6 sm:grid-cols-2">
                        {INCLUDES.map((key) => (
                            <li key={key} className="flex gap-2.5">
                                <Icon
                                    path="m5 12.5 4.5 4.5L19 7"
                                    className="text-brand mt-[0.2em] size-4 flex-none"
                                />
                                <span className="text-[1rem] leading-snug">
                                    {t(`public.lp.offer.includes.${key}`)}
                                </span>
                            </li>
                        ))}
                    </ul>

                    <Link
                        href="/acheter"
                        onClick={() =>
                            track('lp_buy_click', { variant, section: 'offer' })
                        }
                        className="bg-brand text-brand-foreground hover:bg-brand-deep flex min-h-[3.75rem] w-full items-center justify-center gap-3 rounded-lg text-[1.15rem] font-semibold transition-colors"
                    >
                        <Icon
                            path="M4 5h2l2 9h10l2-6H8m10 12a1 1 0 1 1-2 0 1 1 0 0 1 2 0Zm-7 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0Z"
                            className="size-5"
                        />
                        {t('public.lp.offer.buy', {
                            price: formatPrice(price),
                        })}
                    </Link>

                    <ul className="text-brand-muted grid grid-cols-3 gap-x-4">
                        {GUARANTEES.map((item) => (
                            <li
                                key={item.key}
                                className="flex gap-1.5 text-[0.78rem] leading-snug"
                            >
                                <Icon
                                    path={item.icon}
                                    className="mt-[0.15em] size-3.5 flex-none"
                                />
                                {t(`public.lp.offer.guarantees.${item.key}`)}
                            </li>
                        ))}
                    </ul>
                </div>
            </div>
        </Section>
    );
}
