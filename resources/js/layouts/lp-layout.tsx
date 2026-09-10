import { Link, router, usePage } from '@inertiajs/react';
import { useEffect, useState, type PropsWithChildren } from 'react';

import { useUrls } from '@/hooks/useLocale';
import { useFormat } from '@/hooks/useFormat';
import { BrandLogo, useBrand } from '@/brand/BrandProvider';
import { track } from '@/components/landing/track';
import { usePilot } from '@/hooks/usePilot';
import ConsentBanner from '@/components/ConsentBanner';
import { useAnalytics } from '@/hooks/useAnalytics';
import { useT } from '@/hooks/useT';
import PublicFooter from '@/layouts/public-footer';
import type { LocalizedUrls } from '@/types/locale';

/*
 * S00 — La navigation de la variante : ses propres ancres, celles de la page
 * qu'on est en train de lire. Trois entrées, pas cinq : une barre chargée sur
 * une page de vente donne autant de façons de la quitter.
 */
/*
 * Les entrées de la barre. Les ancres restent dans la page de vente ; la FAQ
 * est une page à elle, donc une navigation Inertia.
 */
const navOf = (urls: LocalizedUrls) => [
    { href: urls.how_it_works, key: 'how', inertia: true },
    { href: urls.books, key: 'book', inertia: true },
    { href: urls.faq, key: 'faq', inertia: true },
];

/*
 * S21 — Les pages du site, au pied de page. Ce que la variante porte
 * elle-même reste dans la variante ; les pages dédiées gardent leurs routes.
 */
const discoverOf = (urls: LocalizedUrls) => [
    { href: urls.home, key: 'public.footer.home', inertia: true },
    { href: urls.how_it_works, key: 'public.landing.nav.how', inertia: true },
    { href: urls.books, key: 'public.landing.nav.book', inertia: true },
    {
        href: `${urls.home}#notre-histoire`,
        key: 'public.landing.nav.story',
        inertia: false,
    },
    { href: urls.faq, key: 'public.lp.nav.faq', inertia: true },
    { href: urls.demo, key: 'public.footer.try', inertia: false },
];

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
    const urls = useUrls();
    const fmt = useFormat();
    const formatPrice = fmt.price;
    // La mesure d'audience. Le serveur décide : sans clé, rien ne démarre.
    useAnalytics();

    const t = useT();
    const brand = useBrand();
    const pilot = usePilot();

    const [open, setOpen] = useState(false);

    /*
     * Échap referme, et une navigation aussi.
     *
     * La mise en page **survit** aux visites Inertia : sans cet abonnement, le
     * dépliant resterait ouvert par-dessus la page suivante. Les liens le
     * ferment déjà au clic, mais pas le bouton « précédent » du navigateur.
     */
    useEffect(() => {
        const onKey = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                setOpen(false);
            }
        };

        window.addEventListener('keydown', onKey);
        const stop = router.on('navigate', () => setOpen(false));

        return () => {
            window.removeEventListener('keydown', onKey);
            stop();
        };
    }, []);

    // L'identifiant de la variante vient de la page : la barre ne le devine
    // pas, sinon deux variantes seraient mesurées sous le même nom.
    const { variant } = usePage<{ variant?: string }>().props;

    return (
        <div className="bg-brand-background text-brand-text flex min-h-screen flex-col">
            {/*
             * Le bandeau mène au tunnel : c'est la première ligne de la page,
             * elle annonce le prix, et quelqu'un qui la touche a décidé. Un
             * lien plutôt qu'un `<p>` cliquable — sinon rien ne l'annonce au
             * clavier ni au lecteur d'écran.
             */}
            <Link
                href={urls.checkout_show}
                onClick={() =>
                    track('lp_buy_click', {
                        variant: variant ?? '',
                        section: 'bar',
                    })
                }
                className="bg-brand-deep hover:bg-brand block px-5 py-2.5 text-center text-[0.95rem] text-[#F7F1E6] transition-colors"
            >
                {t('public.lp.nav.bar')}{' '}
                <strong className="font-semibold underline decoration-1 underline-offset-2">
                    {t('public.lp.nav.bar_strong', {
                        price: formatPrice(pilot.pilotPriceCents),
                    })}
                </strong>
            </Link>

            <header className="border-brand-sand border-b">
                {/*
                 * Une seule barre pour les deux tailles d'écran.
                 *
                 * Sur téléphone : le logo, le bouton d'ouverture et l'achat sur
                 * la première ligne ; les entrées et la connexion se déplient
                 * dessous. Sur grand écran tout s'aligne, et le bouton
                 * d'ouverture disparaît. Un second `<nav>` réservé au téléphone
                 * aurait dupliqué quatre liens focusables pour obtenir deux
                 * mises en page — l'ordre des cellules et la visibilité
                 * suffisent.
                 *
                 * Les trois entrées tenaient jusqu'ici sur une deuxième ligne
                 * toujours ouverte : à 360 px elles passaient à trois lignes,
                 * et la connexion en aurait ajouté une quatrième.
                 */}
                <div className="mx-auto flex w-full max-w-[74rem] flex-wrap items-center gap-x-3 gap-y-2 px-4 py-3.5 sm:gap-x-4 sm:px-8 lg:gap-x-8 lg:px-10 lg:py-4">
                    <Link
                        href={urls.home}
                        aria-label={brand.name}
                        className="order-1 mr-auto inline-flex min-h-[2.75rem] items-center lg:mr-4"
                    >
                        <BrandLogo className="font-display text-brand text-[1.25rem] font-semibold sm:text-[1.55rem]" />
                    </Link>

                    {/*
                     * Le bouton d'ouverture : un vrai `<button>` avec
                     * `aria-expanded` et `aria-controls`. Une icône qui
                     * bascule une classe ne dit rien à un lecteur d'écran de
                     * ce qu'elle vient d'ouvrir.
                     */}
                    <button
                        type="button"
                        onClick={() => setOpen(!open)}
                        aria-expanded={open}
                        aria-controls="lp-menu"
                        aria-label={t(
                            open
                                ? 'public.lp.nav.menu_close'
                                : 'public.lp.nav.menu',
                        )}
                        className="text-brand hover:bg-brand/5 order-2 inline-flex size-11 items-center justify-center rounded-md lg:hidden"
                    >
                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            strokeWidth="1.8"
                            strokeLinecap="round"
                            aria-hidden="true"
                            className="size-6"
                        >
                            <path
                                d={
                                    open
                                        ? 'M6 6l12 12M18 6L6 18'
                                        : 'M4 7h16M4 12h16M4 17h16'
                                }
                            />
                        </svg>
                    </button>

                    <Link
                        href={urls.checkout_show}
                        onClick={() =>
                            track('lp_buy_click', {
                                variant: variant ?? '',
                                section: 'header',
                            })
                        }
                        className="bg-brand-accent text-brand-accent-foreground hover:bg-brand-accent-deep order-3 inline-flex min-h-[2.875rem] items-center justify-center rounded-md px-3 text-[0.88rem] font-semibold whitespace-nowrap sm:px-5 sm:text-base lg:order-5"
                    >
                        {t('public.lp.cta.buy')}
                    </Link>

                    <nav
                        id="lp-menu"
                        aria-label="Sections"
                        className={`${open ? 'block' : 'hidden'} order-4 w-full pt-1 pb-1 lg:order-2 lg:mr-auto lg:block lg:w-auto lg:py-0`}
                    >
                        <ul className="flex flex-col gap-y-1 text-[1.02rem] lg:flex-row lg:items-center lg:gap-x-6 lg:text-[0.98rem]">
                            {navOf(urls).map((item) => (
                                <li key={item.key}>
                                    {item.inertia ? (
                                        <Link
                                            href={item.href}
                                            onClick={() => setOpen(false)}
                                            className="hover:text-brand inline-flex min-h-[2.75rem] items-center"
                                        >
                                            {t(`public.lp.nav.${item.key}`)}
                                        </Link>
                                    ) : (
                                        <a
                                            href={item.href}
                                            onClick={() => setOpen(false)}
                                            className="hover:text-brand inline-flex min-h-[2.75rem] items-center"
                                        >
                                            {t(`public.lp.nav.${item.key}`)}
                                        </a>
                                    )}
                                </li>
                            ))}
                        </ul>
                    </nav>

                    {/*
                     * La connexion suit la même visibilité que les entrées :
                     * dans le dépliant sur téléphone, à gauche du bouton
                     * d'achat sur grand écran. Un seul lien, donc une seule
                     * cible pour le clavier.
                     */}
                    <Link
                        href="/login"
                        onClick={() => setOpen(false)}
                        className={`${
                            open ? 'inline-flex' : 'hidden'
                        } text-brand hover:text-brand-deep border-brand-sand order-5 min-h-[2.75rem] w-full items-center border-t pt-2 text-[1.02rem] font-medium lg:order-4 lg:inline-flex lg:w-auto lg:border-0 lg:pt-0 lg:text-[0.98rem]`}
                    >
                        {t('public.lp.nav.login')}
                    </Link>
                </div>
            </header>

            <main className="flex-1">{children}</main>

            <PublicFooter discover={discoverOf(urls)} />

            <ConsentBanner />
        </div>
    );
}
