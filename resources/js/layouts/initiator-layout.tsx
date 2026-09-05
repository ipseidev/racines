import { Link, usePage } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';

import { BrandLogo } from '@/brand/BrandProvider';
import { Toasts } from '@/components/space/Toasts';
import { useStatusToast } from '@/hooks/useStatusToast';
import { useT } from '@/hooks/useT';

const LINKS = [
    { href: '/espace', key: 'dashboard' },
    { href: '/espace/questions', key: 'questions' },
    { href: '/espace/proches', key: 'family' },
    { href: '/espace/reglages', key: 'settings' },
    { href: '/espace/commandes', key: 'orders' },
] as const;

/**
 * Mise en page de l'espace de l'Initiateur·rice.
 *
 * Cinq onglets et rien de plus : c'est un espace d'organisation, consulté une
 * fois par semaine depuis un téléphone, pas un tableau de bord
 * d'administration. Le soulignement d'or glisse sous l'onglet courant, la
 * barre défile au doigt quand l'écran est étroit.
 *
 * Même maison que les autres espaces (crème, lin, cartes blanches, Fraunces en
 * couleur de marque) et un texte un cran plus petit qu'en face des narrateurs :
 * la personne qui organise a la quarantaine ou la soixantaine, et lit sur un
 * téléphone tenu normalement. Les retours du serveur arrivent en toast, en bas
 * de l'écran, là où l'œil revient après un geste (T-149).
 */
export default function InitiatorLayout({ children }: PropsWithChildren) {
    const t = useT();
    const page = usePage();
    const path = page.url.split('?')[0];

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
                <div className="mx-auto w-full max-w-2xl px-6 pt-5">
                    <BrandLogo className="font-display text-brand text-[1.375rem] font-semibold" />

                    <nav
                        aria-label={t('initiator.nav.label')}
                        className="-mx-6 mt-3 [scrollbar-width:none] overflow-x-auto px-6 max-sm:[mask-image:linear-gradient(to_right,black_88%,transparent)] [&::-webkit-scrollbar]:hidden"
                    >
                        <ul className="flex gap-x-1 whitespace-nowrap">
                            {LINKS.map((link) => {
                                const current = path === link.href;

                                return (
                                    <li key={link.href}>
                                        <Link
                                            href={link.href}
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
                className="mx-auto w-full max-w-2xl px-6 py-8 text-[1.0625rem] leading-relaxed"
            >
                <div key={path}>{children}</div>
            </main>

            <Toasts />
        </div>
    );
}
