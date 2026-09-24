import { Link } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';

import { BrandLogo, useBrand } from '@/brand/BrandProvider';
import { useSpacePath } from '@/hooks/useSpacePath';
import { useT } from '@/hooks/useT';

/**
 * Mise en page du tunnel de personnalisation.
 *
 * Ni la navigation de l'espace, ni pied de page : deux minutes, une seule
 * suite d'écrans, et une sortie visible pour qui veut la prendre. La page
 * occupe exactement la hauteur de l'écran, pour que le bouton « Continuer »
 * reste au pouce et que les listes défilent au-dessus de lui.
 */
export default function PersonalizeLayout({ children }: PropsWithChildren) {
    const brand = useBrand();
    const t = useT();
    const space = useSpacePath();

    return (
        <div className="bg-brand-background text-brand-text flex h-[100dvh] flex-col text-[1.0625rem] leading-relaxed">
            <header className="flex-none">
                <div className="mx-auto flex w-full max-w-md items-center justify-between px-5 py-3">
                    <Link href={space('/')} aria-label={brand.name}>
                        <BrandLogo className="font-display text-brand text-[1.4rem] font-semibold" />
                    </Link>
                    <Link
                        href={space('/')}
                        className="text-brand-muted hover:text-brand-text rounded-md px-2 py-2 text-[0.95rem] font-medium"
                    >
                        {t('initiator.personalize.quit')}
                    </Link>
                </div>
            </header>

            <main className="mx-auto flex min-h-0 w-full max-w-md flex-1 flex-col">
                {children}
            </main>
        </div>
    );
}
