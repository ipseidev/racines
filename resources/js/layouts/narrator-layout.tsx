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
 *
 * La hauteur est celle de l'écran (`min-h-dvh`) et le contenu est une colonne
 * souple : la page d'enregistrement s'y étire pour tenir sans défilement
 * (T-139). Les autres pages défilent si elles sont longues.
 */
export default function NarratorLayout({ children }: PropsWithChildren) {
    return (
        <div className="bg-brand-background text-brand-text flex min-h-dvh flex-col">
            <header className="mx-auto w-full max-w-xl px-6 pt-5">
                <BrandLogo className="font-display text-brand text-[1.25rem] font-semibold" />
            </header>

            <main className="enter mx-auto flex w-full max-w-xl flex-1 flex-col px-6 py-5 text-[1.1875rem] leading-relaxed">
                {children}
            </main>
        </div>
    );
}
