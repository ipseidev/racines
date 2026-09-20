import { Link } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';

import { BrandLogo, useBrand } from '@/brand/BrandProvider';
import ConsentBanner from '@/components/ConsentBanner';
import { useAnalytics } from '@/hooks/useAnalytics';
import { useUrls } from '@/hooks/useLocale';
import PublicFooter from '@/layouts/public-footer';

/**
 * Mise en page du tunnel de découverte.
 *
 * Ni navigation, ni bouton d'achat : le parcours a une seule sortie, et une
 * barre de menu au-dessus de la première question est une invitation à aller
 * voir ailleurs. Il ne reste que la marque, ramenée à l'accueil pour qui veut
 * vraiment sortir, et le pied de page légal — qu'on ne retire pas, parce
 * qu'une page qui demande des réponses doit dire qui la sert.
 *
 * Pas de cadenas ni de « paiement sécurisé » comme au tunnel d'achat : rien
 * n'est payé ici, et l'annoncer ferait croire le contraire.
 */
export default function QuizLayout({ children }: PropsWithChildren) {
    const brand = useBrand();
    const urls = useUrls();

    useAnalytics();

    return (
        <div className="bg-brand-background text-brand-text flex min-h-screen flex-col text-[1.125rem] leading-relaxed">
            <header className="border-brand-sand border-b">
                <div className="mx-auto flex w-full max-w-3xl items-center justify-center px-5 py-4">
                    <Link href={urls.home} aria-label={brand.name}>
                        <BrandLogo className="font-display text-brand text-[1.5rem] font-semibold" />
                    </Link>
                </div>
            </header>

            <main className="flex-1">{children}</main>

            <PublicFooter variant="compact" />
            <ConsentBanner />
        </div>
    );
}
