import type { PropsWithChildren } from 'react';

import { BrandLogo } from '@/brand/BrandProvider';

/**
 * Mise en page des pages de la narratrice.
 *
 * Une colonne étroite, un texte large, la marque en haut. Rien d'autre : ces
 * pages s'ouvrent depuis un SMS, sur le téléphone d'une personne de
 * quatre-vingts ans, souvent en 4G. Le contenu entre en fondu, c'est le seul
 * mouvement de la mise en page (T-138). Chaque page dit elle-même où trouver
 * de l'aide quand c'est utile.
 */
export default function NarratorLayout({ children }: PropsWithChildren) {
    return (
        <div className="bg-brand-background text-brand-text flex min-h-screen flex-col">
            <header className="mx-auto w-full max-w-xl px-6 pt-7">
                <BrandLogo className="font-display text-brand text-[1.375rem] font-semibold" />
            </header>

            <main className="enter mx-auto w-full max-w-xl flex-1 px-6 py-8 text-[1.1875rem] leading-relaxed">
                {children}
            </main>
        </div>
    );
}
