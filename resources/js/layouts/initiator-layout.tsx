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
 *
 * Même maison que les autres espaces — crème, lin, cartes blanches, Fraunces en
 * couleur de marque — et un texte un cran plus petit qu'en face des narrateurs :
 * la personne qui organise a la quarantaine ou la soixantaine, et lit sur un
 * téléphone tenu normalement.
 */
export default function InitiatorLayout({ children }: PropsWithChildren) {
    const t = useT();
    const page = usePage();
    const status =
        (page.props.flash as { status?: string | null } | undefined)?.status ??
        null;

    return (
        <div className="bg-brand-background text-brand-text min-h-screen">
            <header className="border-brand-sand border-b">
                <div className="mx-auto w-full max-w-2xl px-6 py-5">
                    <BrandLogo className="font-display text-brand text-[1.375rem] font-semibold" />

                    <nav className="mt-4 flex flex-wrap gap-x-6 gap-y-2 text-base">
                        {LINKS.map((link) => {
                            const current =
                                page.url.split('?')[0] === link.href;

                            return (
                                <Link
                                    key={link.href}
                                    href={link.href}
                                    aria-current={current ? 'page' : undefined}
                                    className={
                                        current
                                            ? 'text-brand decoration-brand-gold font-semibold underline decoration-2 underline-offset-[6px]'
                                            : 'text-brand-muted hover:text-brand'
                                    }
                                >
                                    {t(`initiator.nav.${link.key}`)}
                                </Link>
                            );
                        })}
                    </nav>
                </div>
            </header>

            <main className="mx-auto w-full max-w-2xl px-6 py-8 text-[1.0625rem] leading-relaxed">
                {status !== null && (
                    <p role="status" className="panel mb-6">
                        {status}
                    </p>
                )}

                {children}
            </main>
        </div>
    );
}
