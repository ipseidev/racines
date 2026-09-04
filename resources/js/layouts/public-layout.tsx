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
 *
 * Le contenu n'est pas contraint en largeur ici : la page d'accueil compose
 * des sections pleine largeur, et les autres pages posent elles-mêmes leur
 * colonne de lecture. En-tête et pied partagent la même largeur de conteneur.
 */
export default function PublicLayout({ children }: PropsWithChildren) {
    const t = useT();
    const brand = useBrand();
    const pilot = usePilot();

    return (
        <div className="bg-brand-background text-brand-text flex min-h-screen flex-col">
            {!pilot.legalValidated && (
                <p
                    role="status"
                    className="bg-brand-linen text-brand-muted px-6 py-3 text-center text-base"
                >
                    {t('public.legal.draft_banner')}
                </p>
            )}

            <header className="border-brand-sand border-b">
                <div className="mx-auto flex w-full max-w-6xl items-center justify-between gap-4 px-6 py-5">
                    <Link href="/" aria-label={brand.name}>
                        <BrandLogo className="font-display text-brand text-[1.65rem] font-semibold" />
                    </Link>

                    <Link
                        href="/acheter"
                        className="bg-brand-accent text-brand-accent-foreground hover:bg-brand-accent-deep inline-flex min-h-[2.875rem] items-center justify-center rounded-md px-5 text-base font-semibold"
                    >
                        {t('public.landing.cta')}
                    </Link>
                </div>
            </header>

            <main className="flex-1">{children}</main>

            <footer className="border-brand-sand text-brand-muted border-t">
                <div className="mx-auto flex w-full max-w-6xl flex-wrap items-center justify-between gap-x-10 gap-y-5 px-6 py-10 text-base">
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

                    <a href={`mailto:${brand.support_email}`}>
                        {brand.support_email}
                    </a>
                </div>
            </footer>
        </div>
    );
}
