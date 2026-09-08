import { Head, Link } from '@inertiajs/react';
import { useEffect } from 'react';

import { useBrand } from '@/brand/BrandProvider';
import { BuyButton, SHELL } from '@/components/landing/primitives';
import { formatPrice, usePilot } from '@/hooks/usePilot';
import { useT } from '@/hooks/useT';

type Props = {
    /** `pilot`, `prevente` ou `core`. */
    mode: string;
    /** Le prix vu par ce visiteur, en centimes. */
    price: number;
    /** L'identifiant de la variante, pour la mesure. */
    variant: string;
    /** Le prix du livre numérique, en centimes. */
    ebookPrice: number;
};

/*
 * Les huit familles de questions et leur contenu, dans l'ordre de la page du
 * leader : les plus fréquentes d'abord, puis le produit, l'achat,
 * l'enregistrement, les réglages, le livre, le cadeau, la vie privée, et les
 * comparaisons.
 */
const SECTIONS = [
    {
        key: 'common',
        items: [
            'renewal',
            'shutdown',
            'printed',
            'shipping',
            'language',
            'seniors',
        ],
    },
    { key: 'about', items: ['who', 'how', 'shutdown', 'two_people'] },
    { key: 'pricing', items: ['included', 'why_renewal', 'no_renewal'] },
    {
        key: 'recording',
        items: [
            'submit',
            'writing',
            'more_than_one',
            'missed',
            'length',
            'languages',
        ],
    },
    {
        key: 'customizing',
        items: ['choose', 'change_weekly', 'seniors', 'not_tech'],
    },
    {
        key: 'book',
        items: [
            'create',
            'printed',
            'edit',
            'photos',
            'looks_like',
            'preview',
            'limits',
            'extra',
            'family_order',
            'shipping',
        ],
    },
    { key: 'gifting', items: ['when', 'gift_card', 'printable', 'delivery'] },
    {
        key: 'privacy',
        items: ['private', 'download', 'training', 'delete', 'returns', 'help'],
    },
    {
        key: 'other',
        items: ['storyworth', 'my_life', 'storykeeper', 'no_story_lost'],
    },
] as const;

/**
 * La page « Questions fréquentes » (T-222).
 *
 * Structure de la page du leader : un titre, un chapeau, une barre de
 * raccourcis vers les familles de questions, puis les familles elles-mêmes en
 * dépliants, et un bloc de clôture avec l'achat.
 *
 * Des `<details>` natifs, comme sur l'accueil : ils s'ouvrent au clavier sans
 * une ligne de JavaScript, leur contenu est **dans** le HTML — donc lisible
 * par un moteur et par qui n'a pas de JavaScript — et plusieurs réponses
 * peuvent rester ouvertes. Le seul JavaScript de la page ouvre la réponse
 * visée par un lien profond.
 */
export default function Faq({ price, variant, ebookPrice }: Props) {
    const t = useT();
    const brand = useBrand();
    const pilot = usePilot();

    useEffect(() => {
        const open = () => {
            const hash = window.location.hash;

            if (!hash.startsWith('#q-')) {
                return;
            }

            const target = document.getElementById(hash.slice(1));

            if (target instanceof HTMLDetailsElement) {
                target.open = true;
                target.scrollIntoView({ block: 'start' });
            }
        };

        open();
        window.addEventListener('hashchange', open);

        return () => window.removeEventListener('hashchange', open);
    }, []);

    // Les chiffres cités dans les réponses viennent des réglages, jamais du
    // texte : un prix écrit dans une réponse finirait par contredire le tunnel.
    const values = {
        brand: brand.name,
        price: formatPrice(price),
        extra_copy: formatPrice(pilot.extraCopyPriceCents),
        ebook: formatPrice(ebookPrice),
    };

    return (
        <>
            <Head title={t('public.faq_page.seo_title')} />

            <section className={`${SHELL} flex flex-col gap-6 py-11 lg:py-16`}>
                <h1 className="font-display max-w-[24em] text-[2rem] leading-[1.1] font-medium sm:text-[2.6rem] lg:text-[3rem]">
                    {t('public.faq_page.title')}
                </h1>
                <p className="text-brand-muted max-w-[44em] text-[1.0625rem] leading-relaxed sm:text-[1.15rem]">
                    {t('public.faq_page.lede', values)}
                </p>

                {/* Les raccourcis : de vrais liens, pas un menu déroulant. */}
                <nav aria-label={t('public.faq_page.jump')} className="mt-2">
                    <ul className="flex flex-wrap gap-2.5">
                        {SECTIONS.map((section) => (
                            <li key={section.key}>
                                <a href={`#${section.key}`} className="chip">
                                    {t(
                                        `public.faq_page.categories.${section.key}`,
                                        values,
                                    )}
                                </a>
                            </li>
                        ))}
                    </ul>
                </nav>
            </section>

            {SECTIONS.map((section, index) => (
                <section
                    key={section.key}
                    id={section.key}
                    aria-labelledby={`title-${section.key}`}
                    className={`scroll-mt-6 ${index % 2 === 0 ? 'bg-white' : ''}`}
                >
                    <div
                        className={`${SHELL} grid gap-6 py-11 lg:grid-cols-[4fr_8fr] lg:gap-14 lg:py-14`}
                    >
                        <h2
                            id={`title-${section.key}`}
                            className="font-display text-brand max-w-[16em] text-[1.5rem] leading-tight font-medium sm:text-[1.75rem]"
                        >
                            {t(
                                `public.faq_page.categories.${section.key}`,
                                values,
                            )}
                        </h2>

                        {/*
                         * Deux formes, comme chez eux : les questions les plus
                         * fréquentes se déplient, les autres sont affichées
                         * ouvertes. Une famille de questions qu'on parcourt se
                         * lit mieux dépliée ; un bloc d'appel en tête de page
                         * se lit mieux replié.
                         */}
                        <div className="border-brand-sand divide-brand-sand divide-y border-y">
                            {section.items.map((item) =>
                                section.key === 'common' ? (
                                    <details
                                        key={item}
                                        id={`q-${section.key}-${item}`}
                                        className="group scroll-mt-6"
                                    >
                                        <summary className="text-brand flex min-h-[3.25rem] cursor-pointer items-center justify-between gap-4 py-4 text-[1.05rem] font-semibold">
                                            {t(
                                                `public.faq_page.${section.key}.${item}.q`,
                                                values,
                                            )}
                                            <span
                                                aria-hidden="true"
                                                className="text-brand-muted flex-none text-xl transition-transform group-open:rotate-45"
                                            >
                                                +
                                            </span>
                                        </summary>
                                        <p className="text-brand-muted pr-8 pb-5 text-[1.0625rem] leading-relaxed">
                                            {t(
                                                `public.faq_page.${section.key}.${item}.a`,
                                                values,
                                            )}
                                        </p>
                                    </details>
                                ) : (
                                    <div
                                        key={item}
                                        id={`q-${section.key}-${item}`}
                                        className="flex scroll-mt-6 flex-col gap-2 py-5"
                                    >
                                        <h3 className="text-brand text-[1.05rem] font-semibold">
                                            {t(
                                                `public.faq_page.${section.key}.${item}.q`,
                                                values,
                                            )}
                                        </h3>
                                        <p className="text-brand-muted text-[1.0625rem] leading-relaxed">
                                            {t(
                                                `public.faq_page.${section.key}.${item}.a`,
                                                values,
                                            )}
                                        </p>

                                        {/* La seule réponse qui porte une adresse : celle de l'aide. */}
                                        {section.key === 'privacy' &&
                                            item === 'help' && (
                                                <a
                                                    href={`mailto:${brand.support_email}`}
                                                    className="text-brand inline-flex min-h-[2.75rem] w-fit items-center font-semibold underline decoration-2 underline-offset-4"
                                                >
                                                    {brand.support_email}
                                                </a>
                                            )}
                                    </div>
                                ),
                            )}
                        </div>
                    </div>
                </section>
            ))}

            <section aria-labelledby="faq-closing" className="bg-brand-linen">
                <div
                    className={`${SHELL} flex flex-col items-center gap-5 py-14 text-center lg:py-20`}
                >
                    <h2
                        id="faq-closing"
                        className="font-display max-w-[26em] text-[1.75rem] leading-[1.15] font-medium sm:text-[2.1rem]"
                    >
                        {t('public.faq_page.closing.title')}
                    </h2>
                    <p className="text-brand-muted max-w-[44em] text-[1.0625rem] leading-relaxed sm:text-[1.15rem]">
                        {t('public.faq_page.closing.body', values)}
                    </p>
                    <BuyButton
                        section="faq"
                        variant={variant}
                        price={price}
                        withPrice
                        className="w-full sm:w-fit"
                    />

                    <p className="text-brand-muted mt-4 text-[1rem]">
                        <span className="text-brand font-semibold">
                            {t('public.faq_page.still.title')}
                        </span>{' '}
                        {t('public.faq_page.still.body')}{' '}
                        <a
                            href={`mailto:${brand.support_email}`}
                            className="text-brand font-semibold underline decoration-2 underline-offset-4"
                        >
                            {brand.support_email}
                        </a>
                    </p>

                    <Link
                        href="/comment-ca-marche"
                        className="text-brand mt-1 inline-flex min-h-[2.75rem] items-center text-[1rem] font-semibold underline decoration-2 underline-offset-4"
                    >
                        {t('public.lp.experience.cta')}
                    </Link>
                </div>
            </section>
        </>
    );
}
