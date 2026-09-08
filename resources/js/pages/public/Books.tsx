import { Head } from '@inertiajs/react';
import { useRef, useState } from 'react';

import { useBrand } from '@/brand/BrandProvider';
import { BuyButton, SHELL } from '@/components/landing/primitives';
import Testimonials from '@/components/landing/Testimonials';
import Newsletter from '@/components/marketing/Newsletter';
import { formatPrice } from '@/hooks/usePilot';
import { useT } from '@/hooks/useT';
import { photo } from '@/lib/photo';

type Props = {
    /** `pilot`, `prevente` ou `core`. */
    mode: string;
    /** Le prix vu par ce visiteur, en centimes. */
    price: number;
    /** L'identifiant de la variante, pour la mesure. */
    variant: string;
    /** La fenêtre de bienvenue (T-141) : proposée ou non, et son pourcentage. */
    welcomeOffer: { enabled: boolean; discountPercent: number };
};

/*
 * Les trois onglets et le média qui va avec chacun. La capture de relecture
 * pour les mots, le livre et le téléphone pour la voix, le livre tenu en main
 * pour la décision.
 */
const TABS = [
    { key: 'words', image: 'relecture', native: 780 },
    { key: 'voice', image: 'etape-4', native: 1400 },
    { key: 'control', image: 'etape-3', native: 1400 },
] as const;

const INCLUDES = [
    'questions',
    'record',
    'text',
    'download',
    'book',
    'family',
] as const;

/**
 * La page « Nos livres » (T-224).
 *
 * L'enchaînement des sections est celui de la page « Inside our books » du
 * leader : l'accroche et le prix, une vue annotée du livre, trois onglets, ce
 * que comprend l'achat, les avis, un bloc de fond, l'appel final et la
 * réduction. Les textes sont écrits pour notre produit.
 *
 * Les onglets suivent le motif ARIA : `tablist`, `tab`, `tabpanel`, les
 * flèches déplacent la sélection, et un seul panneau est monté. Un jeu de
 * boutons qui changerait une image sans ces rôles ne dirait rien à un lecteur
 * d'écran de ce qu'il vient de changer.
 */
export default function Books({ price, variant, welcomeOffer }: Props) {
    const t = useT();
    const brand = useBrand();
    const [tab, setTab] = useState(0);
    const tabs = useRef<(HTMLButtonElement | null)[]>([]);

    const move = (index: number) => {
        const next = (index + TABS.length) % TABS.length;

        setTab(next);
        tabs.current[next]?.focus();
    };

    const current = TABS[tab];

    return (
        <>
            <Head title={t('public.seo.books.title')} />

            {/* L'accroche, le prix, l'achat. */}
            <section
                className={`${SHELL} flex flex-col items-center gap-5 py-11 text-center lg:py-16`}
            >
                <h1 className="font-display max-w-[24em] text-[2rem] leading-[1.1] font-medium sm:text-[2.6rem] lg:text-[3rem]">
                    {t('public.books.title', { brand: brand.name })}
                </h1>
                <p className="text-brand-text max-w-[40em] text-[1.0625rem] leading-relaxed sm:text-[1.15rem]">
                    {t('public.books.lede')}
                </p>
                <BuyButton
                    section="books_hero"
                    variant={variant}
                    price={price}
                    withPrice
                    className="w-full sm:w-fit"
                />
                <p className="text-brand-muted text-[0.95rem]">
                    {t('public.books.price_note')}
                </p>
            </section>

            {/* La vue annotée : ce que le livre est, avant ce qu'il contient. */}
            <section
                aria-label={t('public.books.photo_alt', { brand: brand.name })}
            >
                <div className={`${SHELL} pb-11 lg:pb-16`}>
                    <img
                        {...photo('livres', 1024)}
                        sizes="(min-width: 1024px) 68rem, 100vw"
                        alt={t('public.books.photo_alt', {
                            brand: brand.name,
                        })}
                        width="1024"
                        height="541"
                        className="w-full rounded-2xl"
                    />
                </div>
            </section>

            {/* Les trois onglets. */}
            <section
                aria-labelledby="books-tabs"
                className="bg-white py-11 lg:py-16"
            >
                <div className={`${SHELL} flex flex-col gap-8`}>
                    <h2 id="books-tabs" className="sr-only">
                        {t('public.books.includes_title')}
                    </h2>

                    <div
                        role="tablist"
                        aria-labelledby="books-tabs"
                        className="border-brand-sand flex flex-wrap gap-2 border-b"
                    >
                        {TABS.map((item, index) => (
                            <button
                                key={item.key}
                                ref={(node) => {
                                    tabs.current[index] = node;
                                }}
                                type="button"
                                role="tab"
                                id={`tab-${item.key}`}
                                aria-selected={index === tab}
                                aria-controls={`panel-${item.key}`}
                                tabIndex={index === tab ? 0 : -1}
                                onClick={() => setTab(index)}
                                onKeyDown={(event) => {
                                    if (event.key === 'ArrowRight') {
                                        move(index + 1);
                                    }

                                    if (event.key === 'ArrowLeft') {
                                        move(index - 1);
                                    }
                                }}
                                className={`font-display -mb-px min-h-[3rem] border-b-2 px-4 text-[1.15rem] font-medium transition-colors ${
                                    index === tab
                                        ? 'border-brand text-brand'
                                        : 'text-brand-muted hover:text-brand border-transparent'
                                }`}
                            >
                                {t(`public.books.tabs.${item.key}.tab`)}
                            </button>
                        ))}
                    </div>

                    <div
                        role="tabpanel"
                        id={`panel-${current.key}`}
                        aria-labelledby={`tab-${current.key}`}
                        className="grid gap-8 lg:grid-cols-2 lg:items-center lg:gap-14"
                    >
                        <div className="flex flex-col gap-4">
                            <h3 className="font-display text-[1.5rem] leading-tight font-medium sm:text-[1.9rem]">
                                {t(`public.books.tabs.${current.key}.title`)}
                            </h3>
                            <p className="text-brand-text text-[1rem] leading-relaxed sm:text-[1.0625rem]">
                                {t(`public.books.tabs.${current.key}.body`)}
                            </p>
                        </div>

                        {current.key === 'words' ? (
                            /*
                             * La capture de relecture est un portrait de
                             * 780 × 1600 : recadrée en 4/3 comme les
                             * photographies, elle ne montrait qu'une bande du
                             * milieu de l'écran. Elle reprend donc le cadre de
                             * téléphone de l'accueil, qui la montre entière.
                             */
                            <div className="mx-auto w-full max-w-[320px]">
                                <div className="bg-brand-deep rounded-[2rem] p-2.5">
                                    <img
                                        {...photo('relecture', 780)}
                                        sizes="300px"
                                        alt={t(
                                            'public.landing.review.screenshot_alt',
                                        )}
                                        width="780"
                                        height="1600"
                                        loading="lazy"
                                        className="w-full rounded-[1.5rem]"
                                    />
                                </div>
                            </div>
                        ) : (
                            <img
                                key={current.image}
                                {...photo(current.image, current.native)}
                                sizes="(min-width: 1024px) 34rem, 100vw"
                                alt={t(
                                    current.key === 'voice'
                                        ? 'public.landing.how.four.alt'
                                        : 'public.landing.how.three.alt',
                                )}
                                width="1400"
                                height="1050"
                                loading="lazy"
                                className="aspect-[4/3] w-full rounded-xl object-cover"
                            />
                        )}
                    </div>
                </div>
            </section>

            {/* Ce que comprend l'achat. */}
            <section
                aria-labelledby="books-includes"
                className={`${SHELL} grid gap-8 py-11 lg:grid-cols-[4fr_8fr] lg:gap-14 lg:py-16`}
            >
                <h2
                    id="books-includes"
                    className="font-display max-w-[16em] text-[1.75rem] leading-tight font-medium sm:text-[2.1rem]"
                >
                    {t('public.books.includes_title')}
                </h2>

                <ul className="border-brand-sand divide-brand-sand divide-y border-y">
                    {INCLUDES.map((key) => (
                        <li
                            key={key}
                            className="text-brand-text flex gap-3 py-4 text-[1rem] leading-relaxed sm:text-[1.0625rem]"
                        >
                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                strokeWidth="2"
                                strokeLinecap="round"
                                aria-hidden="true"
                                className="text-brand mt-[0.35em] size-4 flex-none"
                            >
                                <path d="m5 12.5 4.5 4.5L19 7" />
                            </svg>
                            {t(`public.books.includes.${key}`)}
                        </li>
                    ))}
                </ul>
            </section>

            {/* Les avis, le même carrousel que l'accueil. */}
            <Testimonials />

            {/* Le bloc de fond : ce qui compte n'est pas l'objet. */}
            <section aria-labelledby="books-why" className="bg-brand-linen">
                <div
                    className={`${SHELL} grid gap-9 py-11 lg:grid-cols-[7fr_5fr] lg:items-center lg:gap-14 lg:py-16`}
                >
                    <div className="flex flex-col gap-5">
                        <h2
                            id="books-why"
                            className="font-display max-w-[22em] text-[1.75rem] leading-[1.15] font-medium sm:text-[2.1rem]"
                        >
                            {t('public.books.why.title')}
                        </h2>
                        <p className="text-brand-text text-[1rem] leading-relaxed sm:text-[1.0625rem]">
                            {t('public.books.why.p1')}
                        </p>
                        <p className="text-brand-text text-[1rem] leading-relaxed sm:text-[1.0625rem]">
                            {t('public.books.why.p2')}
                        </p>
                    </div>

                    <img
                        {...photo('cadeau', 1448)}
                        sizes="(min-width: 1024px) 28rem, 100vw"
                        alt={t('public.lp.easy.photo_alt')}
                        width="1448"
                        height="1086"
                        loading="lazy"
                        className="aspect-[4/3] w-full rounded-2xl object-cover"
                    />
                </div>
            </section>

            {/* L'appel final. */}
            <section
                aria-labelledby="books-closing"
                className={`${SHELL} flex flex-col items-center gap-5 py-14 text-center lg:py-20`}
            >
                <h2
                    id="books-closing"
                    className="font-display max-w-[24em] text-[1.75rem] leading-[1.15] font-medium sm:text-[2.35rem]"
                >
                    {t('public.books.closing.title')}
                </h2>
                <p className="text-brand-text max-w-[38em] text-[1.0625rem] leading-relaxed">
                    {t('public.books.closing.body')}
                </p>
                <BuyButton
                    section="books_closing"
                    variant={variant}
                    price={price}
                    withPrice
                    className="w-full sm:w-fit"
                />
                <p className="text-brand-muted text-[0.95rem]">
                    {formatPrice(price)} ·{' '}
                    {t('public.lp.offer.guarantees.refund')}
                </p>
            </section>

            <Newsletter
                enabled={welcomeOffer.enabled}
                discountPercent={welcomeOffer.discountPercent}
            />
        </>
    );
}
