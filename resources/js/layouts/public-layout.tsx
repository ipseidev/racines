import { Link } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';

import { BrandLogo, useBrand } from '@/brand/BrandProvider';
import { useT } from '@/hooks/useT';
import { usePilot } from '@/hooks/usePilot';

/**
 * Mise en page des pages publiques.
 *
 * Le pied de page porte les liens légaux sur **toutes** les pages, y compris
 * le tunnel d'achat : quelqu'un qui s'apprête à payer doit pouvoir lire les
 * conditions sans revenir en arrière et perdre sa saisie.
 *
 * Le bandeau « à valider par conseil » est global et non propre aux pages
 * légales. Tant que le conseil n'a pas relu, la phase est expérimentale, et
 * une page de vente qui le tairait mentirait par omission.
 */
export default function PublicLayout({ children }: PropsWithChildren) {
    const t = useT();
    const brand = useBrand();
    const pilot = usePilot();

    return (
        <div className="bg-brand-surface text-brand-text flex min-h-screen flex-col">
            {!pilot.legalValidated && (
                <p
                    role="status"
                    className="border-brand-sand text-brand-muted border-b px-6 py-3 text-center text-base"
                >
                    {t('public.legal.draft_banner')}
                </p>
            )}

            <header className="flex items-center justify-between gap-4 px-6 py-6">
                <Link href="/" aria-label={brand.name}>
                    <BrandLogo className="text-base font-medium" />
                </Link>

                <Link
                    href="/acheter"
                    className="bg-brand text-brand-foreground min-h-[2.75rem] rounded-md px-5 py-3 text-base font-medium"
                >
                    {t('public.landing.cta')}
                </Link>
            </header>

            <main className="mx-auto w-full max-w-3xl flex-1 px-6 py-8 text-[1.125rem] leading-relaxed">
                {children}
            </main>

            <footer className="border-brand-sand text-brand-muted mt-12 border-t px-6 py-8 text-base">
                <nav className="flex flex-wrap gap-x-6 gap-y-2">
                    <Link href="/cgv">{t('public.legal.terms')}</Link>
                    <Link href="/confidentialite">
                        {t('public.legal.privacy')}
                    </Link>
                    <Link href="/mentions-legales">
                        {t('public.legal.imprint')}
                    </Link>
                    <Link href="/consentements">
                        {t('public.legal.consents')}
                    </Link>
                </nav>

                <p className="mt-4">
                    <a href={`mailto:${brand.support_email}`}>
                        {brand.support_email}
                    </a>
                </p>
            </footer>
        </div>
    );
}
