import type { PropsWithChildren } from 'react';

import { BrandLogo } from '@/brand/BrandProvider';

/**
 * Mise en page des écrans des proches. Même sobriété que côté narrateur : ces
 * pages s'ouvrent sur tous les téléphones d'une famille, pas sur un poste de
 * travail — et le proche qui écoute a parfois le même âge que la personne qui
 * raconte.
 *
 * Même fond crème, mêmes panneaux de lin et cartes blanches que la page
 * narrateur : une famille qui passe de l'un à l'autre doit reconnaître la même
 * maison (docs/design/README.md).
 */
export default function FamilyLayout({ children }: PropsWithChildren) {
    return (
        <div className="bg-brand-background text-brand-text min-h-screen">
            <header className="mx-auto w-full max-w-xl px-6 pt-7">
                <BrandLogo className="font-display text-brand text-[1.375rem] font-semibold" />
            </header>

            <main className="mx-auto w-full max-w-xl px-6 py-9 text-[1.1875rem] leading-relaxed">
                {children}
            </main>
        </div>
    );
}
