import type { PropsWithChildren } from 'react';

import { BrandLogo } from '@/brand/BrandProvider';

/**
 * Mise en page des écrans narrateur.
 *
 * Contraintes du dossier (convention §11) : texte de 18 px minimum, unités
 * relatives pour respecter l'agrandissement système, aucune animation, aucun
 * compte à rebours. Une seule colonne, très large marge, pour un téléphone
 * tenu à bout de bras.
 */
export default function NarratorLayout({ children }: PropsWithChildren) {
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
