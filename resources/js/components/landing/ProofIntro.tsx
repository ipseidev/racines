import { useBrand } from '@/brand/BrandProvider';
import { Heading, Section, SHELL } from '@/components/landing/primitives';
import { useT } from '@/hooks/useT';

type Review = { text: string; author?: string; source?: string };

/**
 * S09 — L'introduction des preuves.
 *
 * Deux phrases, et leur contenu dépend de ce qu'on a. Sans avis : elles
 * annoncent une **démonstration** — écoutez, lisez, voyez —, et surtout pas
 * d'étoiles, pas de nombre de familles, pas de logo de plateforme. Un chiffre
 * inventé à cet endroit est un mensonge de vente ; un chiffre vrai mais non
 * vérifiable en est un aussi.
 */
export default function ProofIntro({ reviews }: { reviews: Review[] }) {
    const t = useT();
    const brand = useBrand();

    return (
        <Section
            labelledBy="lp-proof-intro"
            className="pt-11 sm:pt-14 lg:pt-20"
        >
            <div className={SHELL}>
                <Heading
                    id="lp-proof-intro"
                    title={
                        reviews.length > 0
                            ? t('public.lp.proof_intro.reviews_title', {
                                  brand: brand.name,
                              })
                            : t('public.lp.proof_intro.title')
                    }
                    lede={
                        reviews.length > 0
                            ? t('public.lp.proof_intro.reviews_body')
                            : t('public.lp.proof_intro.body')
                    }
                    centered
                />
            </div>
        </Section>
    );
}
