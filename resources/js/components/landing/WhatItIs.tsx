import { useBrand } from '@/brand/BrandProvider';
import { BAND, H2, Section, SHELL } from '@/components/landing/primitives';
import { useT } from '@/hooks/useT';

/**
 * S04 — Ce qu'est le produit, juste après le bandeau.
 *
 * Deux colonnes sur grand écran : l'œillet et le titre à gauche, le
 * paragraphe à droite. Et **aucun bouton d'achat** : la fonction de cette
 * section est de clarifier, pas de vendre. Un second bouton posé ici ferait
 * décider avant d'avoir compris.
 */
export default function WhatItIs() {
    const t = useT();
    const brand = useBrand();

    return (
        <Section tone="linen" labelledBy="lp-what" className={BAND}>
            <div
                className={`${SHELL} grid gap-6 lg:grid-cols-2 lg:items-start lg:gap-16`}
            >
                <div className="flex flex-col gap-4">
                    <span className="eyebrow">
                        {t('public.lp.what.eyebrow', { brand: brand.name })}
                    </span>
                    <h2 id="lp-what" className={`${H2} max-w-[20em]`}>
                        {t('public.lp.what.title')}
                    </h2>
                </div>

                <p className="text-[1.0625rem] leading-relaxed sm:text-[1.15rem]">
                    {t('public.lp.what.body', { brand: brand.name })}
                </p>
            </div>
        </Section>
    );
}
