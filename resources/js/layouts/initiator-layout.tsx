import { Link, usePage } from '@inertiajs/react';
import { useEffect, useRef, type PropsWithChildren } from 'react';

import { BrandLogo } from '@/brand/BrandProvider';
import LocaleSwitcher from '@/components/LocaleSwitcher';
import { Toasts } from '@/components/space/Toasts';
import { useStatusToast } from '@/hooks/useStatusToast';
import ConsentBanner from '@/components/ConsentBanner';
import { useAnalytics } from '@/hooks/useAnalytics';
import { useSpace, useSpacePath } from '@/hooks/useSpacePath';
import { useT } from '@/hooks/useT';

/*
 * Les onglets du projet. Relatifs : le préfixe vient du serveur, qui seul
 * sait quel projet est ouvert (`useSpacePath`).
 */
const LINKS = [
    { href: '/', key: 'dashboard' },
    { href: '/questions', key: 'questions' },
    { href: '/proches', key: 'family' },
    { href: '/livre', key: 'book' },
    { href: '/donnees', key: 'data' },
    { href: '/reglages', key: 'settings' },
] as const;

/*
 * Les commandes appartiennent au **compte**, pas au projet : quelqu'un qui a
 * offert deux fois a deux projets et une seule page de commandes, et la
 * rétractation doit rester joignable même sans projet honoré.
 */
const ACCOUNT_LINK = { href: '/espace/commandes', key: 'orders' } as const;

/**
 * Mise en page de l'espace de l'Initiateur·rice.
 *
 * Sept onglets et rien de plus : c'est un espace d'organisation, consulté une
 * fois par semaine, pas un tableau de bord d'administration. Le soulignement
 * d'or glisse sous l'onglet courant, et la barre défile au doigt quand
 * l'écran est étroit.
 *
 * **La colonne s'élargit à partir de `lg`** (21 septembre 2026). Elle était
 * tenue à `max-w-2xl` à toutes les largeurs, parce que cet espace avait été
 * pensé « depuis un téléphone ». Sur un écran de 1440 px, cela donnait 672 px
 * de contenu pour 768 px de vide — et surtout une barre de navigation qui
 * débordait de 175 px : « Les réglages » était coupé en plein mot et « Mes
 * commandes » n'apparaissait pas du tout. Deux onglets sur sept invisibles à
 * la souris, ce n'est plus une question de confort.
 *
 * Le texte, lui, ne s'étire pas avec la colonne : les pages gardent leur
 * largeur de lecture, et c'est la mise en page qui occupe l'espace gagné.
 * Une ligne de cent vingt signes ne se lit pas mieux parce qu'elle tient.
 *
 * Même maison que les autres espaces (crème, lin, cartes blanches, Fraunces en
 * couleur de marque) et un texte un cran plus petit qu'en face des narrateurs :
 * la personne qui organise a la quarantaine ou la soixantaine, et lit sur un
 * téléphone tenu normalement. Les retours du serveur arrivent en toast, en bas
 * de l'écran, là où l'œil revient après un geste (T-149).
 */
function Chevron() {
    return (
        <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="2"
            strokeLinecap="round"
            strokeLinejoin="round"
            aria-hidden="true"
            className="size-4"
        >
            <path d="m6 9 6 6 6-6" />
        </svg>
    );
}

export default function InitiatorLayout({ children }: PropsWithChildren) {
    // La mesure d'audience. Le serveur décide : sur une page à jeton, la prop
    // `analytics` vaut `null` et il n'y a rien à démarrer.
    useAnalytics();

    const t = useT();
    const page = usePage();
    const path = page.url.split('?')[0];

    const spacePath = useSpacePath();
    const space = useSpace();
    const projects = space?.projects ?? [];
    const current = space?.current.narrator ?? '';

    /*
     * Refermer le dépliant sur un clic au-dehors et sur Échap — deux gestes
     * que `<details>` ne connaît pas, et sans lesquels le panneau reste
     * ouvert derrière la page suivante. Même traitement que le sélecteur de
     * langue (T-246).
     */
    const switcher = useRef<HTMLDetailsElement>(null);

    useEffect(() => {
        const close = (event: Event) => {
            const node = switcher.current;

            if (node === null || !node.open) {
                return;
            }

            if (event instanceof KeyboardEvent) {
                if (event.key === 'Escape') {
                    node.open = false;
                }

                return;
            }

            if (!node.contains(event.target as Node)) {
                node.open = false;
            }
        };

        document.addEventListener('click', close);
        document.addEventListener('keydown', close);

        return () => {
            document.removeEventListener('click', close);
            document.removeEventListener('keydown', close);
        };
    }, []);

    useStatusToast();

    return (
        <div className="bg-brand-background text-brand-text min-h-screen">
            <a
                href="#contenu"
                className="focus:bg-brand-surface focus:text-brand sr-only focus:not-sr-only focus:absolute focus:top-3 focus:left-3 focus:z-50 focus:rounded-md focus:px-4 focus:py-2 focus:font-medium"
            >
                {t('initiator.nav.skip')}
            </a>

            <header className="border-brand-sand border-b">
                <div className="mx-auto w-full max-w-2xl px-6 pt-5 lg:max-w-5xl lg:px-8">
                    <div className="flex flex-wrap items-baseline justify-between gap-x-6 gap-y-2">
                        <BrandLogo className="font-display text-brand text-[1.375rem] font-semibold" />

                        {/*
                         * Le sélecteur de projet, et seulement quand il y en a
                         * plusieurs.
                         *
                         * Jusqu'au 21 septembre 2026, un second achat cachait
                         * le premier : l'espace montrait le projet le plus
                         * récent et rien ne disait que les autres existaient.
                         * Un compte de démonstration en portait trois, dont le
                         * seul actif était invisible.
                         *
                         * Un `<details>` natif, comme le sélecteur de langue
                         * (T-246) : ce sont de vrais liens, ils s'ouvrent dans
                         * un onglet, et le clavier les atteint sans script.
                         */}
                        {projects.length > 1 ? (
                            <details className="relative" ref={switcher}>
                                <summary className="text-brand-muted hover:text-brand flex min-h-[2.75rem] cursor-pointer list-none items-center gap-2 text-base marker:content-none [&::-webkit-details-marker]:hidden">
                                    {t('initiator.nav.project_of', {
                                        name: current,
                                    })}
                                    <Chevron />
                                </summary>

                                <ul className="card absolute top-full right-0 z-30 mt-1 min-w-[15rem] p-2 shadow-lg">
                                    {projects.map((project) => (
                                        <li key={project.id}>
                                            {project.href === spacePath('/') ? (
                                                <span
                                                    aria-current="true"
                                                    className="text-brand block rounded-md px-3 py-2.5 font-medium"
                                                >
                                                    {project.narrator}
                                                </span>
                                            ) : (
                                                <Link
                                                    href={project.href}
                                                    className="text-brand-muted hover:bg-brand/5 hover:text-brand block rounded-md px-3 py-2.5 no-underline transition-colors"
                                                >
                                                    {project.narrator}
                                                </Link>
                                            )}
                                        </li>
                                    ))}
                                </ul>
                            </details>
                        ) : null}
                    </div>

                    <nav
                        aria-label={t('initiator.nav.label')}
                        className="-mx-6 mt-3 [scrollbar-width:none] overflow-x-auto px-6 max-sm:[mask-image:linear-gradient(to_right,black_88%,transparent)] [&::-webkit-scrollbar]:hidden"
                    >
                        <ul className="flex gap-x-1 whitespace-nowrap">
                            {[
                                ...LINKS.map((link) => ({
                                    ...link,
                                    to: spacePath(link.href),
                                })),
                                { ...ACCOUNT_LINK, to: ACCOUNT_LINK.href },
                            ].map((link) => {
                                const current = path === link.to;

                                return (
                                    <li key={link.key}>
                                        <Link
                                            href={link.to}
                                            aria-current={
                                                current ? 'page' : undefined
                                            }
                                            className={`tab ${current ? 'tab-current' : ''}`}
                                        >
                                            {t(`initiator.nav.${link.key}`)}
                                        </Link>
                                    </li>
                                );
                            })}
                        </ul>
                    </nav>
                </div>
            </header>

            <main
                id="contenu"
                className="space-shell mx-auto w-full max-w-2xl px-6 py-8 text-[1.0625rem] leading-relaxed lg:max-w-5xl lg:px-8"
            >
                <div key={path}>{children}</div>
            </main>

            {/* La langue de l'espace. Elle est retenue sur le compte, donc
                elle suit la personne d'un appareil à l'autre (T-238). */}
            <footer className="mx-auto w-full max-w-2xl px-6 pb-8 lg:max-w-5xl lg:px-8">
                <LocaleSwitcher />
            </footer>

            <ConsentBanner />

            <Toasts />
        </div>
    );
}
