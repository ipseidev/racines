import { Link } from '@inertiajs/react';

import { BrandLogo, useBrand } from '@/brand/BrandProvider';
import { useT } from '@/hooks/useT';
import { openConsent } from '@/lib/consent';

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
const DISCOVER = [
    { href: '/', key: 'public.footer.home', inertia: true },
    {
        href: '/comment-ca-marche',
        key: 'public.landing.nav.how',
        inertia: true,
    },
    { href: '/nos-livres', key: 'public.landing.nav.book', inertia: true },
    {
        href: '/#notre-histoire',
        key: 'public.landing.nav.story',
        inertia: false,
    },
    {
        href: '/questions-frequentes',
        key: 'public.landing.faq.title',
        inertia: true,
    },
    { href: '/essai', key: 'public.footer.try', inertia: false },
] as const;

const LEGAL = [
    { href: '/cgv', key: 'public.legal.terms' },
    { href: '/confidentialite', key: 'public.legal.privacy' },
    { href: '/mentions-legales', key: 'public.legal.imprint' },
    { href: '/consentements', key: 'public.legal.consents' },
] as const;

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
export default function PublicFooter({
    variant = 'full',
    discover = DISCOVER,
}: Props) {
    const t = useT();
    const brand = useBrand();
    const year = new Date().getFullYear();

    return (
        <footer className="border-brand-sand bg-brand-linen text-brand-text border-t">
            <div className="mx-auto grid w-full max-w-6xl gap-12 px-6 py-14 lg:grid-cols-[5fr_7fr] lg:gap-16">
                <div className="flex flex-col items-start gap-4">
                    <Link href="/" aria-label={brand.name}>
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
                                {discover.map((item) => (
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
                            {LEGAL.map((item) => (
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
                </div>
            </div>
        </footer>
    );
}
