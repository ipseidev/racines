import type { PropsWithChildren } from 'react';

import { BrandLogo } from '@/brand/BrandProvider';

/**
 * Mise en page des écrans des proches. Même sobriété que côté narrateur : ces
 * pages s'ouvrent sur tous les téléphones d'une famille, pas sur un poste de
 * travail.
 */
export default function FamilyLayout({ children }: PropsWithChildren) {
    return (
        <div className="bg-brand-surface text-brand-text min-h-screen">
            <header className="px-6 pt-8">
                <BrandLogo className="text-brand-muted text-base font-medium" />
            </header>

            <main className="mx-auto w-full max-w-xl px-6 py-10 text-[1.125rem] leading-relaxed">
                {children}
            </main>
        </div>
    );
}
