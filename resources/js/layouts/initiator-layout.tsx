import { Link, usePage } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';

import { BrandLogo } from '@/brand/BrandProvider';
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
 * Une barre de navigation à cinq entrées, et rien de plus : c'est un espace
 * d'organisation, consulté une fois par semaine depuis un téléphone, pas un
 * tableau de bord d'administration.
 */
export default function InitiatorLayout({ children }: PropsWithChildren) {
    const t = useT();
    const page = usePage();
    const status =
        (page.props.flash as { status?: string | null } | undefined)?.status ??
        null;

    return (
        <div className="bg-brand-surface text-brand-text min-h-screen">
            <header className="border-brand-muted/40 border-b px-6 py-6">
                <BrandLogo className="text-brand-muted text-base font-medium" />

                <nav className="mt-4 flex flex-wrap gap-x-6 gap-y-2 text-base">
                    {LINKS.map((link) => {
                        const current = page.url.split('?')[0] === link.href;

                        return (
                            <Link
                                key={link.href}
                                href={link.href}
                                aria-current={current ? 'page' : undefined}
                                className={
                                    current
                                        ? 'font-medium underline'
                                        : 'text-brand-muted'
                                }
                            >
                                {t(`initiator.nav.${link.key}`)}
                            </Link>
                        );
                    })}
                </nav>
            </header>

            <main className="mx-auto w-full max-w-2xl px-6 py-8 text-[1.0625rem] leading-relaxed">
                {status !== null && (
                    <p
                        role="status"
                        className="border-brand-muted/40 mb-6 rounded-md border px-4 py-3"
                    >
                        {status}
                    </p>
                )}

                {children}
            </main>
        </div>
    );
}
