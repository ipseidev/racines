import { Link } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';

import { BrandLogo, useBrand } from '@/brand/BrandProvider';
import ConsentBanner from '@/components/ConsentBanner';
import { useAnalytics } from '@/hooks/useAnalytics';
import { useT } from '@/hooks/useT';
import PublicFooter from '@/layouts/public-footer';

function Lock() {
    return (
        <svg
            viewBox="0 0 16 16"
            fill="none"
            stroke="currentColor"
            strokeWidth="1.6"
            aria-hidden="true"
            className="size-4 flex-none"
        >
            <rect x="3" y="7" width="10" height="7" rx="1.5" />
            <path d="M5.5 7V5a2.5 2.5 0 0 1 5 0v2" />
        </svg>
    );
}

/**
 * Mise en page du tunnel d'achat et de la page de merci.
 *
 * Ni la navigation de l'accueil, ni son bouton « J'offre ce livre » : on y
 * est déjà, et un second bouton d'action concurrencerait « Continuer ». À
 * leur place, ce que quelqu'un qui va payer veut lire : la marque, le cadenas,
 * la garantie.
 *
 * Le bandeau « à valider par conseil » reste : tant que le conseil n'a pas
 * relu, une page de vente qui le tairait mentirait par omission.
 *
 * Le tunnel est mesuré comme le reste du site public. Il l'était côté serveur
 * seulement — les événements d'entonnoir de `App\Analytics\Track` — et cette
 * moitié suffit à savoir qui achète, pas à savoir qui renonce : une mesure
 * d'audience qui voit l'accueil et pas `/acheter` ne peut pas dire combien de
 * visiteurs arrivent jusqu'au paiement. Les deux adresses du tunnel sont
 * propres (`/acheter`, `/acheter/merci`), et la chaîne de requête — où Stripe
 * range l'identifiant de session — est retirée avant d'être rapportée.
 */
export default function CheckoutLayout({ children }: PropsWithChildren) {
    const t = useT();
    const brand = useBrand();

    useAnalytics();

    return (
        <div className="bg-brand-background text-brand-text flex min-h-screen flex-col text-[1.125rem] leading-relaxed">
            <header className="border-brand-sand border-b">
                <div className="mx-auto flex w-full max-w-6xl items-center justify-between gap-6 px-6 py-4">
                    <Link href="/" aria-label={brand.name}>
                        <BrandLogo className="font-display text-brand text-[1.65rem] font-semibold" />
                    </Link>

                    <p className="text-brand-muted flex items-center gap-2 text-base">
                        <Lock />
                        <span>{t('public.checkout.secure')}</span>
                        <span className="hidden sm:inline" aria-hidden="true">
                            ·
                        </span>
                        <span className="hidden sm:inline">
                            {t('public.checkout.refund')}
                        </span>
                    </p>
                </div>
            </header>

            <main className="flex-1">{children}</main>

            {/* Sans les pages du site : elles concurrenceraient « Continuer » (T-135). */}
            <PublicFooter variant="compact" />
            <ConsentBanner />
        </div>
    );
}
