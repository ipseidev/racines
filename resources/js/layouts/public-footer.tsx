import { Link } from '@inertiajs/react';

import { BrandLogo, useBrand } from '@/brand/BrandProvider';
import LocaleSwitcher from '@/components/LocaleSwitcher';
import { useUrls } from '@/hooks/useLocale';
import { useT } from '@/hooks/useT';
import { openConsent } from '@/lib/consent';
import type { LocalizedUrls } from '@/types/locale';

type Item = { href: string; key: string; inertia: boolean };

type Props = {
    /**
     * `full` sur les pages publiques : la marque, les pages du site, les
     * informations légales, le contact. `compact` dans le tunnel : sans les
     * pages du site, qui concurrenceraient « Continuer » (T-135).
     */
    variant?: 'full' | 'compact';
    /**
     * Les pages du site, quand elles ne sont pas celles de l'accueil.
     *
     * Une variante de page de vente porte ses propres sections : ses ancres
     * doivent rester dans la page, sinon « Le livre » renvoie le visiteur sur
     * l'accueil, c'est-à-dire hors du test (T-219).
     */
    discover?: readonly Item[];
};

/** Le titre d'une colonne : une étiquette, pas un titre de section. */
const LABEL =
    'text-brand text-[0.78rem] font-semibold tracking-[0.12em] uppercase';

const LINK = 'text-brand-muted hover:text-brand inline-block py-1 text-base';

/*
 * Les pages du site, dans l'ordre de la navigation. Les ancres mènent aux
 * sections de l'accueil ; l'essai est un `<a>` ordinaire, pour que le micro
 * puisse être demandé (T-151).
 */
/*
 * Les adresses viennent du serveur, jamais d'une constante : une page
 * publique a une adresse par langue, et un lien écrit en dur renverrait un
 * visiteur italien sur la version française (T-238).
 */
const discoverOf = (urls: LocalizedUrls): Item[] => [
    { href: urls.home, key: 'public.footer.home', inertia: true },
    { href: urls.how_it_works, key: 'public.landing.nav.how', inertia: true },
    { href: urls.books, key: 'public.landing.nav.book', inertia: true },
    {
        href: `${urls.home}#notre-histoire`,
        key: 'public.landing.nav.story',
        inertia: false,
    },
    { href: urls.faq, key: 'public.landing.faq.title', inertia: true },
    { href: urls.demo, key: 'public.footer.try', inertia: false },
];

const legalOf = (urls: LocalizedUrls) => [
    { href: urls.legal_terms, key: 'public.legal.terms' },
    { href: urls.legal_privacy, key: 'public.legal.privacy' },
    { href: urls.legal_imprint, key: 'public.legal.imprint' },
    { href: urls.legal_consents, key: 'public.legal.consents' },
];

/**
 * Le pied de page des pages publiques et du tunnel.
 *
 * Quatre blocs sur lin : la marque et sa phrase, les pages du site, les
 * informations légales, le contact ; puis une ligne basse avec l'année et
 * l'hébergement. Les titres de colonne ne sont pas des titres de section :
 * une page se lit à ses `<h2>`, et le pied de page n'y a rien à ajouter.
 *
 * Les liens légaux sont sur **toutes** ces pages, tunnel compris : quelqu'un
 * qui s'apprête à payer doit pouvoir lire les conditions sans revenir en
 * arrière et perdre sa saisie.
 */
export default function PublicFooter({ variant = 'full', discover }: Props) {
    const t = useT();
    const brand = useBrand();
    const urls = useUrls();
    const year = new Date().getFullYear();

    const links = discover ?? discoverOf(urls);
    const legal = legalOf(urls);

    return (
        <footer className="border-brand-sand bg-brand-linen text-brand-text border-t">
            <div className="mx-auto grid w-full max-w-6xl gap-12 px-6 py-14 lg:grid-cols-[5fr_7fr] lg:gap-16">
                <div className="flex flex-col items-start gap-4">
                    <Link href={urls.home} aria-label={brand.name}>
                        <BrandLogo className="font-display text-brand text-[1.65rem] font-semibold" />
                    </Link>
                    {brand.tagline !== '' && (
                        <p className="text-brand-muted font-display max-w-[24em] text-[1.15rem] leading-snug italic">
                            {brand.tagline}
                        </p>
                    )}
                </div>

                <div
                    className={`grid gap-10 sm:grid-cols-2 ${
                        variant === 'full' ? 'lg:grid-cols-3' : ''
                    }`}
                >
                    {variant === 'full' && (
                        <nav
                            aria-labelledby="footer-discover"
                            className="flex flex-col gap-3"
                        >
                            <span id="footer-discover" className={LABEL}>
                                {t('public.footer.discover')}
                            </span>
                            <ul className="flex flex-col">
                                {links.map((item) => (
                                    <li key={item.href}>
                                        {item.inertia ? (
                                            <Link
                                                href={item.href}
                                                className={LINK}
                                            >
                                                {t(item.key)}
                                            </Link>
                                        ) : (
                                            <a
                                                href={item.href}
                                                className={LINK}
                                            >
                                                {t(item.key)}
                                            </a>
                                        )}
                                    </li>
                                ))}
                            </ul>
                        </nav>
                    )}

                    <nav
                        aria-labelledby="footer-information"
                        className="flex flex-col gap-3"
                    >
                        <span id="footer-information" className={LABEL}>
                            {t('public.footer.information')}
                        </span>
                        <ul className="flex flex-col">
                            {legal.map((item) => (
                                <li key={item.href}>
                                    <Link href={item.href} className={LINK}>
                                        {t(item.key)}
                                    </Link>
                                </li>
                            ))}
                            {/*
                             * Retirer son accord doit être aussi simple que le
                             * donner (T-227) : ce bouton rouvre le bandeau. Un
                             * bouton et non un lien — il ne mène nulle part.
                             */}
                            <li>
                                <button
                                    type="button"
                                    onClick={openConsent}
                                    className={`${LINK} text-left`}
                                >
                                    {t('public.consent.manage')}
                                </button>
                            </li>
                        </ul>
                    </nav>

                    <div className="flex flex-col gap-3">
                        <span className={LABEL}>
                            {t('public.footer.contact')}
                        </span>
                        <ul className="flex flex-col">
                            <li>
                                <a
                                    href={`mailto:${brand.support_email}`}
                                    className={LINK}
                                >
                                    {brand.support_email}
                                </a>
                            </li>
                            {brand.support_phone !== null && (
                                <li>
                                    <a
                                        href={`tel:${brand.support_phone.replace(/\s+/g, '')}`}
                                        className={LINK}
                                    >
                                        {brand.support_phone}
                                    </a>
                                </li>
                            )}
                        </ul>
                    </div>
                </div>
            </div>

            <div className="border-brand-sand border-t">
                <div className="text-brand-muted mx-auto flex w-full max-w-6xl flex-wrap items-center justify-between gap-x-8 gap-y-2 px-6 py-5 text-[0.9rem]">
                    <span>
                        {t('public.footer.copyright', {
                            year,
                            brand: brand.name,
                        })}
                    </span>
                    <span>{t('public.footer.hosting')}</span>
                    {/* Le sélecteur de langue : cinq liens vers la même page,
                        chacun nommé dans sa propre langue (T-238). */}
                    <LocaleSwitcher
                        tone="footer"
                        className="w-full sm:w-auto"
                    />
                </div>
            </div>
        </footer>
    );
}
