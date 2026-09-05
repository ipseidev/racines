import type { PropsWithChildren } from 'react';
import { usePage } from '@inertiajs/react';

import { BrandLogo } from '@/brand/BrandProvider';
import { Toasts } from '@/components/space/Toasts';
import { useStatusToast } from '@/hooks/useStatusToast';
import { useT } from '@/hooks/useT';

/**
 * Mise en page des écrans des proches. Même sobriété que côté narrateur : ces
 * pages s'ouvrent sur tous les téléphones d'une famille, pas sur un poste de
 * travail — et le proche qui écoute a parfois le même âge que la personne qui
 * raconte.
 *
 * Même fond crème, mêmes panneaux de lin et cartes blanches que la page
 * narrateur : une famille qui passe de l'un à l'autre doit reconnaître la même
 * maison (docs/design/README.md).
 *
 * Trois choses ajoutées après le checkpoint du bloc 08 (T-158) : l'évitement
 * clavier que les autres espaces avaient déjà, le filet sous l'en-tête qui
 * pose le bandeau de marque, et les toasts. Les retours du serveur arrivaient
 * jusque-là en paragraphe au milieu de la page, à un endroit que l'œil ne
 * regarde pas après avoir pressé un bouton en bas.
 */
export default function FamilyLayout({ children }: PropsWithChildren) {
    const t = useT();
    const path = usePage().url.split('?')[0];

    useStatusToast();

    return (
        <div className="bg-brand-background text-brand-text min-h-screen">
            <a
                href="#contenu"
                className="focus:bg-brand-surface focus:text-brand sr-only focus:not-sr-only focus:absolute focus:top-3 focus:left-3 focus:z-50 focus:rounded-md focus:px-4 focus:py-2 focus:font-medium"
            >
                {t('family.nav.skip')}
            </a>

            <header className="border-brand-sand border-b">
                <div className="mx-auto w-full max-w-xl px-6 py-5">
                    <BrandLogo className="font-display text-brand text-[1.375rem] font-semibold" />
                </div>
            </header>

            <main
                id="contenu"
                className="mx-auto w-full max-w-xl px-6 py-9 text-[1.1875rem] leading-relaxed"
            >
                {/*
                 * La clé de chemin rejoue l'entrée en fondu à chaque page :
                 * sans elle, React réutilise l'arbre et rien ne bouge quand on
                 * passe d'une histoire à la suivante.
                 */}
                <div key={path}>{children}</div>
            </main>

            <Toasts />
        </div>
    );
}
