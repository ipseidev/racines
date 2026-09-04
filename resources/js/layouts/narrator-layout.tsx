import type { PropsWithChildren } from 'react';

import { BrandLogo } from '@/brand/BrandProvider';

/**
 * Mise en page des écrans narrateur.
 *
 * Contraintes du dossier (convention §11) : texte de 18 px minimum — 19 ici,
 * parce que la personne a quatre-vingts ans —, unités relatives pour respecter
 * l'agrandissement système, aucune animation, aucun compte à rebours. Une
 * seule colonne, très large marge, pour un téléphone tenu à bout de bras.
 *
 * Le fond est le crème de la page, pas le blanc des cartes : c'est ce qui
 * laisse les panneaux de lin et les cartes blanches se distinguer du reste
 * (docs/design/README.md).
 */
export default function NarratorLayout({ children }: PropsWithChildren) {
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
