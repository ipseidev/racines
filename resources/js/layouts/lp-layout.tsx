import { Link, usePage } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';

import { BrandLogo, useBrand } from '@/brand/BrandProvider';
import { track } from '@/components/landing/track';
import { formatPrice, usePilot } from '@/hooks/usePilot';
import { useAnalytics } from '@/hooks/useAnalytics';
import { useT } from '@/hooks/useT';
import PublicFooter from '@/layouts/public-footer';

/*
 * S00 — La navigation de la variante : ses propres ancres, celles de la page
 * qu'on est en train de lire. Trois entrées, pas cinq : une barre chargée sur
 * une page de vente donne autant de façons de la quitter.
 */
const NAV = [
    { href: '#comment-ca-marche', key: 'how' },
    { href: '#le-livre', key: 'book' },
] as const;

/*
 * S21 — Les pages du site, au pied de page. Ce que la variante porte
 * elle-même reste dans la variante ; les pages dédiées gardent leurs routes.
 */
const DISCOVER = [
    { href: '/', key: 'public.footer.home', inertia: true },
    {
        href: '/comment-ca-marche',
        key: 'public.landing.nav.how',
        inertia: true,
    },
    { href: '#le-livre', key: 'public.landing.nav.book', inertia: false },
    {
        href: '#notre-histoire',
        key: 'public.landing.nav.story',
        inertia: false,
    },
    { href: '/essai', key: 'public.footer.try', inertia: false },
] as const;

/**
 * La mise en page des variantes de page de vente (T-219).
 *
 * Elle ne remplace pas `PublicLayout` et ne la modifie pas : l'accueil est le
 * témoin du test, et une barre de navigation partagée qui apprendrait à
 * changer d'ancres selon la page serait une modification du témoin. Ici, la
 * barre pointe **dans** la page, le pied de page reçoit la même liste, et rien
 * de ce qui sert l'accueil n'a bougé.
 *
 * Le bandeau d'offre est conservé, la barre n'est pas collante et il n'y a pas
 * de barre d'achat flottante : cumuler les deux sur un téléphone mange le
 * tiers de l'écran, et le brief l'interdit. Ni compte à rebours, ni bandeau
 * clignotant.
 */
export default function LpLayout({ children }: PropsWithChildren) {
    // La mesure d'audience. Le serveur décide : sans clé, rien ne démarre.
    useAnalytics();

    const t = useT();
    const brand = useBrand();
    const pilot = usePilot();

    // L'identifiant de la variante vient de la page : la barre ne le devine
    // pas, sinon deux variantes seraient mesurées sous le même nom.
    const { variant } = usePage<{ variant?: string }>().props;

    return (
        <div className="bg-brand-background text-brand-text flex min-h-screen flex-col">
            <p className="bg-brand-deep px-5 py-2.5 text-center text-[0.95rem] text-[#F7F1E6]">
                {t('public.lp.nav.bar', {
                    price: formatPrice(pilot.pilotPriceCents),
                })}
            </p>

            <header className="border-brand-sand border-b">
                {/*
                 * Une seule barre, qui se replie : le logo et le bouton sur la
                 * première ligne, les trois ancres sur la seconde sur
                 * téléphone, et tout aligné dès le grand écran. Un second
                 * `<nav>` réservé au téléphone aurait dupliqué trois liens
                 * focusables pour obtenir deux mises en page.
                 */}
                <div className="mx-auto flex w-full max-w-[74rem] flex-wrap items-center justify-between gap-x-8 gap-y-3 px-5 py-4 sm:px-8 lg:px-10">
                    <Link
                        href="/"
                        aria-label={brand.name}
                        className="order-1 inline-flex min-h-[2.75rem] items-center"
                    >
                        <BrandLogo className="font-display text-brand text-[1.55rem] font-semibold" />
                    </Link>

                    <nav
                        aria-label="Sections"
                        className="order-3 w-full lg:order-2 lg:mr-auto lg:w-auto"
                    >
                        <ul className="flex flex-wrap items-center gap-x-6 gap-y-2 text-[0.98rem]">
                            {NAV.map((item) => (
                                <li key={item.key}>
                                    <a
                                        href={item.href}
                                        className="hover:text-brand inline-flex min-h-[2.75rem] items-center"
                                    >
                                        {t(`public.lp.nav.${item.key}`)}
                                    </a>
                                </li>
                            ))}
                        </ul>
                    </nav>

                    <Link
                        href="/acheter"
                        onClick={() =>
                            track('lp_buy_click', {
                                variant: variant ?? '',
                                section: 'header',
                            })
                        }
                        className="bg-brand-accent text-brand-accent-foreground hover:bg-brand-accent-deep order-2 inline-flex min-h-[2.875rem] items-center justify-center rounded-md px-5 text-base font-semibold lg:order-3"
                    >
                        {t('public.lp.cta.buy')}
                    </Link>
                </div>
            </header>

            <main className="flex-1">{children}</main>

            <PublicFooter discover={DISCOVER} />
        </div>
    );
}
