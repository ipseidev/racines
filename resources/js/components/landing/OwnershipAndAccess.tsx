import { Link } from '@inertiajs/react';

import { useUrls } from '@/hooks/useLocale';
import { useFormat } from '@/hooks/useFormat';
import { useBrand } from '@/brand/BrandProvider';
import { BAND, Section, SHELL } from '@/components/landing/primitives';
import { track } from '@/components/landing/track';
import { useT } from '@/hooks/useT';
import { photo } from '@/lib/photo';

/*
 * Les trois colonnes de la carte. La troisième porte une étiquette
 * « facultatif » : sans elle, « renouvelez » se lit comme un abonnement, ce
 * que ce produit n'est pas.
 */
const COLUMNS = [
    {
        key: 'forever',
        icon: 'M12 6.5v13M12 6.5A4 4 0 0 0 4 6.5v11a4 4 0 0 1 8-2m0-9a4 4 0 0 1 8 0v11a4 4 0 0 0-8-2',
    },
    { key: 'download', icon: 'M12 4v11m0 0-4-4m4 4 4-4M5 19h14' },
    {
        key: 'renew',
        icon: 'M20 12a8 8 0 1 1-2.3-5.6M20 4v4h-4',
    },
] as const;

const CHECKS = ['refund', 'shipping', 'book'] as const;

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
 * S08 — Ce que l'achat comprend, et ce qui reste après.
 *
 * La structure est celle du leader : le titre et le chapeau centrés, un
 * intertitre posé sur un filet, une grande carte claire qui tient les trois
 * colonnes, le bandeau doré, le prix et le bouton face à une image, puis une
 * bande de confiance en pied de section.
 *
 * Le prix vient des réglages, pas d'ici : deux endroits qui écrivent un prix
 * finissent par en afficher deux, et c'est celui-là que le tunnel encaisse.
 */
export default function OwnershipAndAccess({
    variant,
    price,
}: {
    variant: string;
    price: number;
}) {
    const urls = useUrls();
    const fmt = useFormat();
    const formatPrice = fmt.price;
    const t = useT();
    const brand = useBrand();

    return (
        <Section labelledBy="lp-access" className={BAND}>
            <div className={`${SHELL} flex flex-col gap-8`}>
                <div className="flex flex-col items-center gap-4 text-center">
                    <h2
                        id="lp-access"
                        className="font-display max-w-[30em] text-[1.9rem] leading-[1.12] font-medium sm:text-[2.35rem] lg:text-[2.75rem]"
                    >
                        {t('public.lp.access.title')}
                    </h2>
                    <p className="text-brand-muted max-w-[44em] text-[1.0625rem] leading-relaxed sm:text-[1.15rem]">
                        {t('public.lp.access.lede', { brand: brand.name })}
                    </p>
                </div>

                {/* L'intertitre posé sur un filet, comme la référence. */}
                <div className="flex items-center gap-5">
                    <span className="bg-brand-sand h-px flex-1" />
                    <span className="text-brand-accent text-[0.78rem] font-semibold tracking-[0.14em] whitespace-nowrap uppercase">
                        {t('public.lp.access.includes_label')}
                    </span>
                    <span className="bg-brand-sand h-px flex-1" />
                </div>

                <div className="bg-brand-surface border-brand-sand flex flex-col gap-8 rounded-2xl border p-6 sm:p-9 lg:p-12">
                    <ul className="grid gap-8 lg:grid-cols-3 lg:gap-10">
                        {COLUMNS.map((column) => (
                            <li
                                key={column.key}
                                className="flex flex-col gap-3"
                            >
                                <span className="bg-brand text-brand-gold flex size-11 items-center justify-center rounded-full">
                                    <Icon
                                        path={column.icon}
                                        className="size-5"
                                    />
                                </span>
                                <h3 className="font-display text-brand flex flex-wrap items-center gap-x-3 gap-y-1.5 text-[1.3rem] leading-tight font-medium">
                                    {t(`public.lp.access.${column.key}.title`)}
                                    {column.key === 'renew' && (
                                        <span className="border-brand-sand text-brand-muted rounded-full border px-2.5 py-0.5 text-[0.65rem] font-semibold tracking-[0.1em] uppercase">
                                            {t('public.lp.access.renew.badge')}
                                        </span>
                                    )}
                                </h3>
                                <p className="text-brand-muted text-[1.0625rem] leading-relaxed">
                                    {t(`public.lp.access.${column.key}.body`)}
                                </p>
                            </li>
                        ))}
                    </ul>

                    {/*
                     * Le bandeau doré : la phrase qui tient tout le bloc.
                     *
                     * Le cadenas est **dans** le flux du texte et non un frère
                     * en flexbox : en flexbox, la phrase prenait toute la
                     * ligne, se centrait dans sa propre boîte, et le cadenas
                     * restait seul contre le bord gauche.
                     */}
                    <p className="bg-brand-gold/30 text-brand font-display rounded-lg px-5 py-4 text-center text-[1.05rem] leading-snug italic sm:text-[1.15rem]">
                        <svg
                            viewBox="0 0 16 16"
                            fill="none"
                            stroke="currentColor"
                            strokeWidth="1.6"
                            aria-hidden="true"
                            className="mr-2 inline-block size-4 align-[-0.15em]"
                        >
                            <rect x="3" y="7" width="10" height="7" rx="1.5" />
                            <path d="M5.5 7V5a2.5 2.5 0 0 1 5 0v2" />
                        </svg>
                        {t('public.lp.access.banner')}
                    </p>

                    <div className="grid gap-8 lg:grid-cols-2 lg:items-center lg:gap-12">
                        <div className="flex flex-col items-center gap-5">
                            <span className="font-display text-brand text-[3.25rem] leading-none font-medium tabular-nums">
                                {formatPrice(price)}
                            </span>

                            <Link
                                href={urls.checkout_show}
                                onClick={() =>
                                    track('lp_buy_click', {
                                        variant,
                                        section: 'access',
                                    })
                                }
                                className="bg-brand text-brand-foreground hover:bg-brand-deep flex min-h-[3.5rem] w-full items-center justify-center gap-2 rounded-lg text-[1.15rem] font-semibold transition-colors"
                            >
                                {t('public.lp.access.buy')} →
                            </Link>

                            <ul className="text-brand-muted flex flex-wrap justify-center gap-x-6 gap-y-1.5 text-center text-[0.85rem]">
                                {CHECKS.map((key) => (
                                    <li
                                        key={key}
                                        className="flex items-center gap-1.5"
                                    >
                                        <Icon
                                            path="m5 12.5 4.5 4.5L19 7"
                                            className="text-brand size-3.5 flex-none"
                                        />
                                        {t(`public.lp.access.checks.${key}`)}
                                    </li>
                                ))}
                            </ul>
                        </div>

                        {/* Le filet de couleur autour de l'image, comme la référence. */}
                        <img
                            {...photo('etape-4')}
                            sizes="(min-width: 1024px) 28rem, 100vw"
                            alt={t('public.lp.access.photo_alt')}
                            width="1400"
                            height="1050"
                            loading="lazy"
                            className="border-brand-gold aspect-[4/3] w-full rounded-xl border-4 object-cover"
                        />
                    </div>
                </div>
            </div>
        </Section>
    );
}
